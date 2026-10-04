<?php

use App\Actions\Issues\BuildIssueTimeline;
use App\Enums\IssueActivityType;
use App\Events\IssueTimelineChanged;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\Label;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Ada Lovelace']);
    $this->board = Board::factory()->withDefaultStatuses()->withSprints()->withMember($this->user)->create();
    [$this->todo, $this->progress, $this->done] = $this->board->statuses;
    $this->actingAs($this->user);
});

/**
 * @return list<array{type: string, data: array<string, mixed>|null}>
 */
function recordedActivity(Issue $issue): array
{
    return $issue->activities()->orderBy('id')->get()
        ->map(fn (IssueActivity $activity) => ['type' => $activity->type->value, 'data' => $activity->data])
        ->all();
}

test('creating an issue records who created it', function () {
    $this->post(route('boards.issues.store', $this->board), ['name' => 'Build login', 'role' => 'task'])->assertSessionHasNoErrors();

    $issue = Issue::where('name', 'Build login')->sole();
    $activity = $issue->activities()->sole();

    expect($activity->type)->toBe(IssueActivityType::Created)
        ->and($activity->user_id)->toBe($this->user->id)
        ->and($activity->data)->toBe(['name' => 'Build login']);
});

test('each kind of change is recorded with the names involved', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create(['name' => 'Old name']);
    $sprint = Sprint::factory()->for($this->board)->current()->create(['slug' => '2026W41']);
    $bug = Label::factory()->for($this->board)->create(['name' => 'Bug']);
    $other = User::factory()->create(['name' => 'Grace Hopper']);
    $this->board->users()->attach($other, ['role' => 'member']);

    $this->put(route('issues.update', $issue), ['name' => 'New name'])->assertSessionHasNoErrors();
    $this->put(route('issues.update', $issue), ['status_id' => $this->progress->id]);
    $this->put(route('issues.update', $issue), ['assigned_id' => $other->id]);
    $this->put(route('issues.update', $issue), ['assigned_id' => null]);
    $this->put(route('issues.update', $issue), ['sprint_id' => $sprint->id]);
    $this->put(route('issues.update', $issue), ['label_ids' => [$bug->id]]);
    $this->put(route('issues.update', $issue), ['label_ids' => []]);

    expect(recordedActivity($issue))->toBe([
        ['type' => 'renamed', 'data' => ['from' => 'Old name', 'to' => 'New name']],
        ['type' => 'moved', 'data' => ['from' => 'To Do', 'to' => 'In Progress']],
        ['type' => 'assigned', 'data' => ['from' => null, 'to' => 'Grace Hopper']],
        ['type' => 'assigned', 'data' => ['from' => 'Grace Hopper', 'to' => null]],
        ['type' => 'sprint_changed', 'data' => ['from' => null, 'to' => '2026W41']],
        ['type' => 'labels_changed', 'data' => ['added' => ['Bug'], 'removed' => []]],
        ['type' => 'labels_changed', 'data' => ['added' => [], 'removed' => ['Bug']]],
    ])->and($issue->activities()->pluck('user_id')->unique()->all())->toBe([$this->user->id]);
});

test('closing and reopening are recorded instead of plain moves', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->put(route('issues.update', $issue), ['status_id' => $this->done->id]);
    $this->put(route('issues.update', $issue), ['status_id' => $this->progress->id]);

    expect(recordedActivity($issue))->toBe([
        ['type' => 'closed', 'data' => ['from' => 'To Do', 'to' => 'Done']],
        ['type' => 'reopened', 'data' => ['from' => 'Done', 'to' => 'In Progress']],
    ]);
});

test('changes that leave values as they were record nothing', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create(['name' => 'Same', 'assigned_id' => $this->user->id]);
    $label = Label::factory()->for($this->board)->create();
    $issue->labels()->attach($label);
    Event::fake([IssueTimelineChanged::class]);

    $this->put(route('issues.update', $issue), [
        'name' => 'Same',
        'status_id' => $this->todo->id,
        'assigned_id' => $this->user->id,
        'description' => 'A new description',
        'label_ids' => [$label->id],
    ])->assertSessionHasNoErrors();
    $this->put(route('issues.update', $issue), ['sort' => 42.5]);

    expect($issue->activities()->count())->toBe(0);
    Event::assertNotDispatched(IssueTimelineChanged::class);
});

test('several changes in one request are all recorded and announced once', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create(['name' => 'Before']);
    Event::fake([IssueTimelineChanged::class]);

    $this->put(route('issues.update', $issue), ['name' => 'After', 'status_id' => $this->progress->id, 'assigned_id' => $this->user->id]);

    expect(array_column(recordedActivity($issue), 'type'))->toBe(['renamed', 'moved', 'assigned']);
    Event::assertDispatchedTimes(IssueTimelineChanged::class, 1);
    Event::assertDispatched(IssueTimelineChanged::class, fn (IssueTimelineChanged $event) => $event->issueId === $issue->id
        && $event->boardId === $this->board->id
        && $event->broadcastOn()->name === 'private-boards.'.$this->board->id);
});

test('entries keep the names from when they happened', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();
    $this->put(route('issues.update', $issue), ['status_id' => $this->progress->id]);

    $this->progress->update(['name' => 'Doing']);
    $this->todo->delete();

    expect(recordedActivity($issue))->toBe([['type' => 'moved', 'data' => ['from' => 'To Do', 'to' => 'In Progress']]]);
});

test('entries survive their author\'s account being deleted', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();
    $this->put(route('issues.update', $issue), ['name' => 'Renamed']);
    Comment::factory()->for($issue)->create(['user_id' => $this->user->id]);

    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasNoErrors();

    expect($issue->activities()->sole()->user_id)->toBeNull()
        ->and($issue->comments()->sole()->user_id)->toBeNull();
});

describe('the timeline on the issue page', function () {
    test('loads after the page and merges activity with comments oldest first', function () {
        $this->travelTo('2026-10-05 09:00:00');
        $issue = Issue::factory()->inStatus($this->todo)->create();
        IssueActivity::factory()->for($issue)->create(['type' => IssueActivityType::Created, 'data' => ['name' => 'x'], 'user_id' => $this->user->id, 'created_at' => now()->subHours(3)]);
        $comment = Comment::factory()->for($issue)->create(['user_id' => $this->user->id, 'body' => 'First **comment**', 'created_at' => now()->subHours(2)]);
        IssueActivity::factory()->for($issue)->create(['type' => IssueActivityType::Moved, 'data' => ['from' => 'To Do', 'to' => 'Done'], 'user_id' => null, 'created_at' => now()->subHour()]);

        $this->get(route('issues.show', $issue))->assertInertia(fn (Assert $page) => $page
            ->missing('timeline')
            ->where('timelineLimit', BuildIssueTimeline::LIMIT)
            ->loadDeferredProps(fn (Assert $deferred) => $deferred
                ->has('timeline', 3)
                ->where('timeline.0.kind', 'activity')
                ->where('timeline.0.type', 'created')
                ->where('timeline.0.user.name', 'Ada Lovelace')
                ->where('timeline.1.kind', 'comment')
                ->where('timeline.1.id', $comment->id)
                ->where('timeline.1.body', 'First **comment**')
                ->where('timeline.1.body_html', "<p>First <strong>comment</strong></p>\n")
                ->where('timeline.2.kind', 'activity')
                ->where('timeline.2.type', 'moved')
                ->where('timeline.2.user', null)));
    });

    test('only the latest entries are kept when there are too many', function () {
        $issue = Issue::factory()->inStatus($this->todo)->create();
        Comment::factory()->for($issue)->count(BuildIssueTimeline::LIMIT + 5)->sequence(fn ($sequence) => [
            'user_id' => $this->user->id,
            'body' => 'Comment '.$sequence->index,
            'created_at' => now()->subMinutes(1000 - $sequence->index),
        ])->create();

        $this->get(route('issues.show', $issue))->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $deferred) => $deferred
                ->has('timeline', BuildIssueTimeline::LIMIT)
                ->where('timeline.0.body', 'Comment 5')
                ->where('timeline.'.(BuildIssueTimeline::LIMIT - 1).'.body', 'Comment '.(BuildIssueTimeline::LIMIT + 4))));
    });

    test('says what the viewer may do with each comment', function () {
        $issue = Issue::factory()->inStatus($this->todo)->create();
        $author = User::factory()->create();
        $this->board->users()->attach($author, ['role' => 'member']);
        Comment::factory()->for($issue)->create(['user_id' => $this->user->id, 'created_at' => now()->subMinutes(2)]);
        Comment::factory()->for($issue)->create(['user_id' => $author->id, 'created_at' => now()->subMinute()]);

        $this->get(route('issues.show', $issue))->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $deferred) => $deferred
            ->where('timeline.0.can_update', true)->where('timeline.0.can_delete', true)
            ->where('timeline.1.can_update', false)->where('timeline.1.can_delete', false)));

        $this->board->users()->updateExistingPivot($this->user->id, ['role' => 'admin']);

        $this->get(route('issues.show', $issue))->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $deferred) => $deferred
            ->where('timeline.1.can_update', false)->where('timeline.1.can_delete', true)));
    });
});
