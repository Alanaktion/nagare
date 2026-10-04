<?php

use App\Models\Board;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Sprint;
use App\Models\User;
use App\Notifications\IssueChanged;
use App\Notifications\IssueCommented;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->actor = User::factory()->create(['name' => 'Ada']);
    $this->watcher = User::factory()->create(['name' => 'Grace']);
    $this->board = Board::factory()->withDefaultStatuses()->withSprints()->withMember($this->actor)->create(['name' => 'Roadmap']);
    $this->board->users()->attach($this->watcher, ['role' => 'member']);
    [$this->todo, $this->progress, $this->done] = $this->board->statuses;
    $this->issue = Issue::factory()->inStatus($this->todo)->create(['name' => 'Fix login', 'description' => 'Before', 'assigned_id' => null]);
    $this->issue->watchers()->attach([$this->actor->id, $this->watcher->id]);
    $this->actingAs($this->actor);
    Notification::fake();
});

/**
 * @return list<array<string, mixed>>
 */
function sentChanges(User $user, Issue $issue): array
{
    $changes = [];

    Notification::assertSentTo($user, IssueChanged::class, function (IssueChanged $notification) use (&$changes, $user, $issue) {
        $changes = $notification->toArray($user)['changes'];

        return $notification->issueId === $issue->id;
    });

    return $changes;
}

describe('what notifies', function () {
    test('a status change', function () {
        $this->put(route('issues.update', $this->issue), ['status_id' => $this->progress->id]);

        expect(sentChanges($this->watcher, $this->issue))->toBe([['type' => 'moved', 'from' => 'To Do', 'to' => 'In Progress', 'to_you' => false]]);
    });

    test('closing and reopening', function () {
        $this->put(route('issues.update', $this->issue), ['status_id' => $this->done->id]);
        expect(sentChanges($this->watcher, $this->issue)[0])->toMatchArray(['type' => 'closed', 'from' => 'To Do', 'to' => 'Done']);

        Notification::fake();
        $this->put(route('issues.update', $this->issue), ['status_id' => $this->todo->id]);
        expect(sentChanges($this->watcher, $this->issue)[0])->toMatchArray(['type' => 'reopened', 'from' => 'Done', 'to' => 'To Do']);
    });

    test('a new description, with the start of it', function () {
        $this->put(route('issues.update', $this->issue), ['description' => "A   new\n\ndescription"]);

        expect(sentChanges($this->watcher, $this->issue))->toBe([['type' => 'description', 'excerpt' => 'A new description', 'to_you' => false]]);
    });

    test('removing the description', function () {
        $this->put(route('issues.update', $this->issue), ['description' => '']);

        expect(sentChanges($this->watcher, $this->issue)[0])->toMatchArray(['type' => 'description', 'excerpt' => null]);
    });

    test('a comment', function () {
        $this->post(route('issues.comments.store', $this->issue), ['body' => "Looks   **good**\nto me"]);

        Notification::assertSentTo($this->watcher, IssueCommented::class, fn (IssueCommented $notification) => $notification->toArray($this->watcher) === [
            'kind' => 'commented',
            'issue_id' => $this->issue->id,
            'issue_name' => 'Fix login',
            'board_id' => $this->board->id,
            'board_name' => 'Roadmap',
            'actor' => ['id' => $this->actor->id, 'name' => 'Ada'],
            'comment_id' => Comment::sole()->id,
            'excerpt' => 'Looks **good** to me',
        ]);
    });

    test('an assignment tells the new assignee it is for them, and everyone else who', function () {
        $assignee = User::factory()->create(['name' => 'Linus']);
        $this->board->users()->attach($assignee, ['role' => 'member']);

        $this->put(route('issues.update', $this->issue), ['assigned_id' => $assignee->id]);

        expect(sentChanges($assignee, $this->issue))->toBe([['type' => 'assigned', 'from' => null, 'to' => 'Linus', 'to_id' => $assignee->id, 'to_you' => true]])
            ->and(sentChanges($this->watcher, $this->issue)[0])->toMatchArray(['to' => 'Linus', 'to_you' => false]);
    });

    test('an assignment from the issue form, which sends ids as strings, is still for the assignee', function () {
        $this->put(route('issues.update', $this->issue), ['assigned_id' => (string) $this->watcher->id]);

        expect(sentChanges($this->watcher, $this->issue))->toBe([['type' => 'assigned', 'from' => null, 'to' => 'Grace', 'to_id' => $this->watcher->id, 'to_you' => true]]);
    });

    test('unassigning', function () {
        $this->issue->update(['assigned_id' => $this->watcher->id]);

        $this->put(route('issues.update', $this->issue), ['assigned_id' => null]);

        expect(sentChanges($this->watcher, $this->issue)[0])->toMatchArray(['type' => 'assigned', 'from' => 'Grace', 'to' => null]);
    });

    test('creating an issue assigned to someone', function () {
        $this->post(route('boards.issues.store', $this->board), ['name' => 'New work', 'role' => 'task', 'assigned_id' => $this->watcher->id]);

        $issue = Issue::where('name', 'New work')->sole();
        expect(sentChanges($this->watcher, $issue)[0])->toMatchArray(['type' => 'assigned', 'to_id' => $this->watcher->id, 'to_you' => true]);
    });

    test('several changes made together arrive as one notification', function () {
        $this->put(route('issues.update', $this->issue), ['status_id' => $this->progress->id, 'assigned_id' => $this->watcher->id, 'description' => 'New']);

        Notification::assertSentToTimes($this->watcher, IssueChanged::class, 1);
        expect(array_column(sentChanges($this->watcher, $this->issue), 'type'))->toBe(['moved', 'assigned', 'description']);
    });
});

describe('what does not notify', function () {
    test('changes other than status, comments, description and assignment', function () {
        $sprint = Sprint::factory()->for($this->board)->current()->create();
        $label = Label::factory()->for($this->board)->create();

        $this->put(route('issues.update', $this->issue), ['name' => 'Renamed']);
        $this->put(route('issues.update', $this->issue), ['sprint_id' => $sprint->id]);
        $this->put(route('issues.update', $this->issue), ['label_ids' => [$label->id]]);
        $this->put(route('issues.update', $this->issue), ['sort' => 9.5]);
        $this->put(route('issues.update', $this->issue), ['description' => 'Before', 'status_id' => $this->todo->id]);

        Notification::assertNothingSent();
    });

    test('editing or deleting a comment', function () {
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->actor->id]);

        $this->put(route('comments.update', $comment), ['body' => 'Edited']);
        $this->delete(route('comments.destroy', $comment));

        Notification::assertNothingSent();
    });

    test('the person who made the change', function () {
        $this->put(route('issues.update', $this->issue), ['status_id' => $this->progress->id]);
        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi']);

        Notification::assertNotSentTo($this->actor, IssueChanged::class);
        Notification::assertNotSentTo($this->actor, IssueCommented::class);
    });

    test('people who are not watching', function () {
        $bystander = User::factory()->create();
        $this->board->users()->attach($bystander, ['role' => 'member']);

        $this->put(route('issues.update', $this->issue), ['status_id' => $this->progress->id]);

        Notification::assertNotSentTo($bystander, IssueChanged::class);
    });

    test('watchers who are no longer on the board', function () {
        $this->board->users()->detach($this->watcher);

        $this->put(route('issues.update', $this->issue), ['status_id' => $this->progress->id]);
        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi']);

        Notification::assertNothingSentTo($this->watcher);
    });

    test('assigning an issue to yourself does not notify you, and an unassigned new issue notifies no one', function () {
        $this->put(route('issues.update', $this->issue), ['assigned_id' => $this->actor->id]);
        $this->post(route('boards.issues.store', $this->board), ['name' => 'Unassigned', 'role' => 'task']);

        Notification::assertNotSentTo($this->actor, IssueChanged::class);
        Notification::assertSentToTimes($this->watcher, IssueChanged::class, 1);
    });
});

describe('delivery', function () {
    test('goes to the database and live updates, and to email unless turned off', function () {
        $this->put(route('issues.update', $this->issue), ['status_id' => $this->progress->id]);
        $quiet = User::factory()->create(['email_notifications' => false]);
        $this->board->users()->attach($quiet, ['role' => 'member']);
        $this->issue->watchers()->attach($quiet);

        Notification::fake();
        $this->put(route('issues.update', $this->issue), ['status_id' => $this->done->id]);

        Notification::assertSentTo($this->watcher, IssueChanged::class, fn ($notification, array $channels) => $channels === ['database', 'broadcast', 'mail']);
        Notification::assertSentTo($quiet, IssueChanged::class, fn ($notification, array $channels) => $channels === ['database', 'broadcast']);
    });

    test('email says who did what and links to the issue', function () {
        $notification = new IssueChanged($this->issue, $this->actor, [
            ['type' => 'moved', 'from' => 'To Do', 'to' => 'In Progress'],
            ['type' => 'assigned', 'from' => null, 'to' => 'Grace', 'to_id' => $this->watcher->id],
            ['type' => 'description', 'excerpt' => 'The new text'],
        ]);

        $mail = $notification->toMail($this->watcher);
        $body = (string) $mail->render();

        expect($mail->subject)->toBe('[Roadmap] Ada updated "Fix login"')
            ->and($body)->toContain('Ada moved it from To Do to In Progress.')
            ->toContain('Ada assigned it to you.')
            ->toContain('Ada updated the description.')
            ->toContain('The new text')
            ->toContain(route('issues.show', $this->issue))
            ->toContain(route('notifications-settings.edit'));
    });

    test('email for a comment quotes it', function () {
        $comment = Comment::factory()->for($this->issue)->create(['body' => 'Please check the logs']);
        $mail = (new IssueCommented($this->issue, $this->actor, $comment))->toMail($this->watcher);

        expect($mail->subject)->toBe('[Roadmap] Ada commented on "Fix login"')->and((string) $mail->render())->toContain('Please check the logs');
    });

    test('a notification keeps what it said even if the issue changes before it is sent', function () {
        $notification = new IssueCommented($this->issue, $this->actor, Comment::factory()->for($this->issue)->create(['body' => 'Hello']));
        $this->issue->update(['name' => 'Renamed since']);

        expect($notification->toArray($this->watcher)['issue_name'])->toBe('Fix login');
    });
});
