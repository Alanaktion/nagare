<?php

use App\Enums\IssueRole;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    [$this->todo, $this->progress, $this->done] = $this->board->statuses;
});

test('guests are redirected to login', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->post(route('boards.issues.store', $this->board), ['name' => 'x', 'role' => 'task'])->assertRedirect(route('login'));
    $this->get(route('issues.show', $issue))->assertRedirect(route('login'));
    $this->put(route('issues.update', $issue), ['name' => 'x'])->assertRedirect(route('login'));
    $this->delete(route('issues.destroy', $issue))->assertRedirect(route('login'));
});

test('non-members cannot create, view, update or delete issues', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();
    $this->actingAs(User::factory()->create());

    $this->post(route('boards.issues.store', $this->board), ['name' => 'x', 'role' => 'task'])->assertForbidden();
    $this->get(route('issues.show', $issue))->assertForbidden();
    $this->put(route('issues.update', $issue), ['name' => 'x'])->assertForbidden();
    $this->delete(route('issues.destroy', $issue))->assertForbidden();

    expect($this->board->issues()->count())->toBe(1)->and($issue->fresh()->name)->not->toBe('x');
});

test('the board page includes its issues and members', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create(['assigned_id' => $this->user->id]);
    Issue::factory()->create();

    $this->actingAs($this->user)->get(route('boards.show', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 1)
            ->where('issues.data.0.id', $issue->id)
            ->where('issues.data.0.assignee.id', $this->user->id)
            ->has('members.data', 1));
});

test('an issue without a status goes into the first status, last in the column', function () {
    Issue::factory()->inStatus($this->todo)->create(['sort' => 4]);

    $this->actingAs($this->user)->post(route('boards.issues.store', $this->board), [
        'name' => 'Write docs',
        'description' => 'Details',
        'role' => 'task',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $issue = Issue::where('name', 'Write docs')->firstOrFail();
    expect($issue->status_id)->toBe($this->todo->id)
        ->and($issue->author_id)->toBe($this->user->id)
        ->and($issue->role)->toBe(IssueRole::Task)
        ->and($issue->sort)->toBe(5.0)
        ->and($issue->closed_at)->toBeNull();
});

test('an issue created in a closing status is closed', function () {
    $this->actingAs($this->user)->post(route('boards.issues.store', $this->board), [
        'name' => 'Already done',
        'role' => 'task',
        'status_id' => $this->done->id,
    ])->assertSessionHasNoErrors();

    expect(Issue::firstOrFail()->closed_at)->not->toBeNull();
});

test('issue creation validates its input', function (array $overrides, string $errorKey) {
    $this->actingAs($this->user)
        ->post(route('boards.issues.store', $this->board), array_merge(['name' => 'Valid', 'role' => 'task'], $overrides))
        ->assertSessionHasErrors($errorKey);

    expect(Issue::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'missing role' => [['role' => null], 'role'],
    'epic role' => [['role' => 'epic'], 'role'],
    'story on a board without stories' => [['role' => 'story'], 'role'],
    'parent on a board without stories' => [['parent_id' => 1], 'parent_id'],
]);

test('status and assignee must belong to the board', function () {
    $foreignStatus = Board::factory()->withDefaultStatuses()->create()->statuses->first();

    $this->actingAs($this->user)
        ->post(route('boards.issues.store', $this->board), [
            'name' => 'x', 'role' => 'task',
            'status_id' => $foreignStatus->id,
            'assigned_id' => User::factory()->create()->id,
        ])->assertSessionHasErrors(['status_id', 'assigned_id']);
});

test('members can be assigned', function () {
    $teammate = User::factory()->create();
    $this->board->users()->attach($teammate, ['role' => 'member']);

    $this->actingAs($this->user)
        ->post(route('boards.issues.store', $this->board), ['name' => 'x', 'role' => 'task', 'assigned_id' => $teammate->id])
        ->assertSessionHasNoErrors();

    expect(Issue::firstOrFail()->assigned_id)->toBe($teammate->id);
});

describe('boards with stories', function () {
    beforeEach(function () {
        $this->board->update(['has_stories' => true]);
        $this->story = Issue::factory()->story()->inStatus($this->todo)->create();
    });

    test('stories can be created and tasks can be placed under a story', function () {
        $this->actingAs($this->user);

        $this->post(route('boards.issues.store', $this->board), ['name' => 'New story', 'role' => 'story'])
            ->assertSessionHasNoErrors();
        $this->post(route('boards.issues.store', $this->board), ['name' => 'Child', 'role' => 'task', 'parent_id' => $this->story->id])
            ->assertSessionHasNoErrors();

        expect(Issue::where('name', 'New story')->first()->role)->toBe(IssueRole::Story)
            ->and(Issue::where('name', 'Child')->first()->parent_id)->toBe($this->story->id);
    });

    test('a story cannot have a parent and a task parent must be a story on the same board', function () {
        $task = Issue::factory()->inStatus($this->todo)->create();
        $foreignStory = Issue::factory()->story()->create();
        $this->actingAs($this->user);

        $this->post(route('boards.issues.store', $this->board), ['name' => 'x', 'role' => 'story', 'parent_id' => $this->story->id])
            ->assertSessionHasErrors('parent_id');
        $this->post(route('boards.issues.store', $this->board), ['name' => 'x', 'role' => 'task', 'parent_id' => $task->id])
            ->assertSessionHasErrors('parent_id');
        $this->post(route('boards.issues.store', $this->board), ['name' => 'x', 'role' => 'task', 'parent_id' => $foreignStory->id])
            ->assertSessionHasErrors('parent_id');
    });

    test('deleting a story detaches its tasks', function () {
        $task = Issue::factory()->inStatus($this->todo)->create(['parent_id' => $this->story->id]);

        $this->actingAs($this->user)->delete(route('issues.destroy', $this->story))->assertRedirect();

        expect($this->story->fresh()->trashed())->toBeTrue()
            ->and($task->fresh()->parent_id)->toBeNull();
    });
});

test('updating an issue changes its fields', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->actingAs($this->user)->put(route('issues.update', $issue), [
        'name' => 'Renamed',
        'description' => 'New description',
        'assigned_id' => $this->user->id,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $issue->refresh();
    expect($issue->name)->toBe('Renamed')
        ->and($issue->description)->toBe('New description')
        ->and($issue->assigned_id)->toBe($this->user->id);
});

test('partial updates leave other fields alone and status cannot be blank', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create(['name' => 'Keep me']);

    $this->actingAs($this->user)->put(route('issues.update', $issue), ['status_id' => $this->progress->id])
        ->assertSessionHasNoErrors();
    expect($issue->fresh()->name)->toBe('Keep me')->and($issue->fresh()->status_id)->toBe($this->progress->id);

    $this->actingAs($this->user)->put(route('issues.update', $issue), ['status_id' => null])
        ->assertSessionHasErrors('status_id');
    $this->actingAs($this->user)->put(route('issues.update', $issue), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('moving to a closing status sets closed_at, keeps it between closing statuses, and moving out clears it', function () {
    $secondClosing = $this->board->statuses()->create(['name' => 'Cancelled', 'sort' => 3, 'is_closed' => true]);
    $issue = Issue::factory()->inStatus($this->todo)->create();
    $this->actingAs($this->user);

    $this->put(route('issues.update', $issue), ['status_id' => $this->done->id]);
    $closedAt = $issue->fresh()->closed_at;
    expect($closedAt)->not->toBeNull();

    $this->travel(1)->hour();
    $this->put(route('issues.update', $issue), ['status_id' => $secondClosing->id]);
    expect($issue->fresh()->closed_at->equalTo($closedAt))->toBeTrue();

    $this->put(route('issues.update', $issue), ['status_id' => $this->progress->id]);
    expect($issue->fresh()->closed_at)->toBeNull();
});

test('the issue page shows the issue', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->actingAs($this->user)->get(route('issues.show', $issue))
        ->assertInertia(fn (Assert $page) => $page
            ->component('issues/Show')
            ->where('issue.data.id', $issue->id)
            ->has('board.data.statuses', 3)
            ->has('members.data', 1));
});

test('members can delete issues and deleted issues are gone', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->actingAs($this->user)->delete(route('issues.destroy', $issue))
        ->assertRedirect(route('boards.show', $this->board));

    expect($issue->fresh()->trashed())->toBeTrue();
    $this->get(route('issues.show', $issue))->assertNotFound();
});
