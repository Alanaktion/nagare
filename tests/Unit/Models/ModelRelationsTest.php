<?php

declare(strict_types=1);

use App\Models\Board;
use App\Models\Issue;
use App\Models\Status;
use App\Models\User;

test('board has many statuses ordered by sort', function (): void {
    $board = Board::factory()->create();
    Status::factory()->for($board)->create(['sort' => 3, 'name' => 'Done']);
    Status::factory()->for($board)->create(['sort' => 1, 'name' => 'To Do']);
    Status::factory()->for($board)->create(['sort' => 2, 'name' => 'In Progress']);

    $statuses = $board->statuses()->get();

    expect($statuses->pluck('name')->toArray())->toBe(['To Do', 'In Progress', 'Done']);
});

test('board has many issues', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    Issue::factory()->count(3)->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    expect($board->issues()->count())->toBe(3);
});

test('board belongs to a user', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create(['user_id' => $user->id]);

    expect($board->user->id)->toBe($user->id);
});

test('board has many users via pivot', function (): void {
    $board = Board::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $board->users()->attach($user1, ['role' => Board::ROLE_USER]);
    $board->users()->attach($user2, ['role' => Board::ROLE_ADMIN]);

    expect($board->users()->count())->toBe(2);
});

test('board type descriptions contains kanban and scrum', function (): void {
    $keys = array_column(Board::TYPE_DESCRIPTIONS, 'key');

    expect($keys)->toContain('kanban');
    expect($keys)->toContain('scrum');
});

test('board can be soft deleted', function (): void {
    $board = Board::factory()->create();

    $board->delete();

    expect(Board::withTrashed()->find($board->id))->not->toBeNull();
    expect(Board::find($board->id))->toBeNull();
});

test('issue belongs to a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    expect($issue->board->id)->toBe($board->id);
});

test('issue belongs to a status', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    expect($issue->status->id)->toBe($status->id);
});

test('issue can be assigned to a user', function (): void {
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create([
        'board_id' => $board->id,
        'status_id' => $status->id,
        'author_id' => $author->id,
        'assigned_id' => $assignee->id,
    ]);

    expect($issue->assigned->id)->toBe($assignee->id);
});

test('issue can be soft deleted', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    $issue->delete();

    expect(Issue::withTrashed()->find($issue->id))->not->toBeNull();
    expect(Issue::find($issue->id))->toBeNull();
});

test('user has created issues', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    Issue::factory()->count(2)->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    expect($user->createdIssues()->count())->toBe(2);
});

test('user has assigned issues', function (): void {
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    Issue::factory()->count(2)->create([
        'board_id' => $board->id,
        'status_id' => $status->id,
        'author_id' => $author->id,
        'assigned_id' => $assignee->id,
    ]);

    expect($assignee->assignedIssues()->count())->toBe(2);
});
