<?php

declare(strict_types=1);

use App\Models\Board;
use App\Models\Issue;
use App\Models\Status;
use App\Models\User;

test('guests cannot view users index', function (): void {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('authenticated users can view users index', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertSuccessful();
});

test('authenticated users can view a user profile', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.show', $other))
        ->assertSuccessful();
});

test('guests cannot view a user profile', function (): void {
    $user = User::factory()->create();

    $this->get(route('users.show', $user))->assertRedirect(route('login'));
});

test('user profile loads boards and assigned issues', function (): void {
    $user = User::factory()->create();
    $viewer = User::factory()->create();
    $board = Board::factory()->create(['user_id' => $user->id]);
    $board->users()->attach($user, ['role' => Board::ROLE_ADMIN]);
    $status = Status::factory()->for($board)->create();
    Issue::factory()->create([
        'board_id' => $board->id,
        'status_id' => $status->id,
        'author_id' => $user->id,
        'assigned_id' => $user->id,
    ]);

    $this->actingAs($viewer)
        ->get(route('users.show', $user))
        ->assertSuccessful();
});
