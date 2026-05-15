<?php

declare(strict_types=1);

use App\Models\Board;
use App\Models\Status;
use App\Models\User;

test('guests cannot access boards index', function (): void {
    $this->get(route('boards.index'))->assertRedirect(route('login'));
});

test('authenticated users can view boards index', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('boards.index'))
        ->assertSuccessful();
});

test('authenticated users can view the board create page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('boards.create'))
        ->assertSuccessful();
});

test('guests cannot view board create page', function (): void {
    $this->get(route('boards.create'))->assertRedirect(route('login'));
});

test('authenticated users can create a board', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('boards.store'), [
        'name' => 'My Board',
        'type' => 'kanban',
        'statuses' => [
            ['name' => 'To Do', 'is_closed' => false],
            ['name' => 'Done', 'is_closed' => true],
        ],
    ]);

    $response->assertRedirect();

    $board = Board::where('name', 'My Board')->first();
    expect($board)->not->toBeNull();
    expect($board->type)->toBe('kanban');
    expect($board->statuses()->count())->toBe(2);
});

test('board name is required', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('boards.store'), [
        'name' => '',
        'type' => 'kanban',
        'statuses' => [['name' => 'To Do', 'is_closed' => false]],
    ])->assertSessionHasErrors('name');
});

test('board type must be valid', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('boards.store'), [
        'name' => 'Test',
        'type' => 'invalid',
        'statuses' => [['name' => 'To Do', 'is_closed' => false]],
    ])->assertSessionHasErrors('type');
});

test('board statuses are required', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('boards.store'), [
        'name' => 'Test',
        'type' => 'kanban',
        'statuses' => [],
    ])->assertSessionHasErrors('statuses');
});

test('board member can view a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    Status::factory()->for($board)->create();

    $this->actingAs($user)
        ->get(route('boards.show', $board))
        ->assertSuccessful();
});

test('non-member cannot view a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $this->actingAs($user)
        ->get(route('boards.show', $board))
        ->assertForbidden();
});

test('board admin can update a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_ADMIN]);
    $status = Status::factory()->for($board)->create();

    $this->actingAs($user)->patch(route('boards.update', $board), [
        'name' => 'Updated Board',
        'type' => 'kanban',
        'statuses' => [
            ['id' => $status->id, 'name' => 'To Do', 'is_closed' => false],
        ],
    ])->assertRedirect(route('boards.index'));

    expect($board->fresh()->name)->toBe('Updated Board');
});

test('board user can update a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);
    $status = Status::factory()->for($board)->create();

    $this->actingAs($user)->patch(route('boards.update', $board), [
        'name' => 'Updated Board',
        'type' => 'kanban',
        'statuses' => [
            ['id' => $status->id, 'name' => 'To Do', 'is_closed' => false],
        ],
    ])->assertRedirect(route('boards.index'));
});

test('non-member cannot update a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $status = Status::factory()->for($board)->create();

    $this->actingAs($user)->patch(route('boards.update', $board), [
        'name' => 'Hacked',
        'type' => 'kanban',
        'statuses' => [
            ['id' => $status->id, 'name' => 'To Do', 'is_closed' => false],
        ],
    ])->assertForbidden();
});

test('board admin can delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_ADMIN]);

    $this->actingAs($user)
        ->delete(route('boards.destroy', $board))
        ->assertRedirect(route('boards.index'));

    expect($board->fresh()->trashed())->toBeTrue();
});

test('board user cannot delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);

    $this->actingAs($user)
        ->delete(route('boards.destroy', $board))
        ->assertForbidden();
});

test('non-member cannot delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $this->actingAs($user)
        ->delete(route('boards.destroy', $board))
        ->assertForbidden();
});
