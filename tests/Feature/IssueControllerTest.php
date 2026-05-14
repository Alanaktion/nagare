<?php

declare(strict_types=1);

use App\Models\Board;
use App\Models\Issue;
use App\Models\Status;
use App\Models\User;

test('guests cannot create issues', function (): void {
    $this->post(route('issues.store'))->assertRedirect(route('login'));
});

test('authenticated users can create an issue', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    $status = Status::factory()->for($board)->create(['sort' => 1]);

    $response = $this->actingAs($user)->postJson(route('issues.store'), [
        'board_id' => $board->id,
        'name' => 'Fix the bug',
        'description' => 'Something is broken.',
    ]);

    $response->assertSuccessful();
    expect(Issue::where('name', 'Fix the bug')->exists())->toBeTrue();
});

test('issue defaults to first status when none provided', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    $firstStatus = Status::factory()->for($board)->create(['sort' => 1]);
    Status::factory()->for($board)->create(['sort' => 2]);

    $response = $this->actingAs($user)->postJson(route('issues.store'), [
        'board_id' => $board->id,
        'name' => 'New Issue',
    ]);

    $response->assertSuccessful();
    $issue = Issue::where('name', 'New Issue')->first();
    expect($issue)->not->toBeNull();
    expect($issue->status_id)->toBe($firstStatus->id);
});

test('issue name is required', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    Status::factory()->for($board)->create();

    $this->actingAs($user)->post(route('issues.store'), [
        'board_id' => $board->id,
        'name' => '',
    ])->assertSessionHasErrors('name');
});

test('board_id is required to create issue', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('issues.store'), [
        'name' => 'Test',
    ])->assertSessionHasErrors('board_id');
});

test('authenticated users can view an issue', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('issues.show', $issue))
        ->assertSuccessful();
});

test('guests cannot view issues', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    $this->get(route('issues.show', $issue))->assertRedirect(route('login'));
});

test('board member can update an issue', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $user->id]);

    $response = $this->actingAs($user)->patchJson(route('issues.update', $issue), [
        'name' => 'Updated Issue Name',
    ]);

    $response->assertSuccessful();
    expect($issue->fresh()->name)->toBe('Updated Issue Name');
});

test('non-member cannot update an issue', function (): void {
    $outsider = User::factory()->create();
    $author = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $status->id, 'author_id' => $author->id]);

    $this->actingAs($outsider)->patchJson(route('issues.update', $issue), [
        'name' => 'Hacked',
    ])->assertForbidden();
});

test('updating issue status to closed sets closed_at', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    $openStatus = Status::factory()->for($board)->create(['is_closed' => false]);
    $closedStatus = Status::factory()->for($board)->create(['is_closed' => true]);
    $issue = Issue::factory()->create(['board_id' => $board->id, 'status_id' => $openStatus->id, 'author_id' => $user->id]);

    $this->actingAs($user)->patchJson(route('issues.update', $issue), [
        'status_id' => $closedStatus->id,
    ]);

    expect($issue->fresh()->closed_at)->not->toBeNull();
});

test('updating issue status to open clears closed_at', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    $openStatus = Status::factory()->for($board)->create(['is_closed' => false]);
    $closedStatus = Status::factory()->for($board)->create(['is_closed' => true]);
    $issue = Issue::factory()->create([
        'board_id' => $board->id,
        'status_id' => $closedStatus->id,
        'author_id' => $user->id,
        'closed_at' => now(),
    ]);

    $this->actingAs($user)->patchJson(route('issues.update', $issue), [
        'status_id' => $openStatus->id,
    ]);

    expect($issue->fresh()->closed_at)->toBeNull();
});
