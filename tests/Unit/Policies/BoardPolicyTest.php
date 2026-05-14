<?php

declare(strict_types=1);

use App\Models\Board;
use App\Models\User;
use App\Policies\BoardPolicy;

test('viewAny is always allowed', function (): void {
    $policy = new BoardPolicy();

    expect($policy->viewAny())->toBeTrue();
});

test('create is always allowed', function (): void {
    $policy = new BoardPolicy();

    expect($policy->create())->toBeTrue();
});

test('user can view a board they are a member of', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);

    $policy = new BoardPolicy();

    expect($policy->view($user, $board))->toBeTrue();
});

test('admin can view a board they own', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_ADMIN]);

    $policy = new BoardPolicy();

    expect($policy->view($user, $board))->toBeTrue();
});

test('non-member cannot view a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $policy = new BoardPolicy();

    expect($policy->view($user, $board))->toBeFalse();
});

test('member can update a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);

    $policy = new BoardPolicy();

    expect($policy->update($user, $board))->toBeTrue();
});

test('non-member cannot update a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $policy = new BoardPolicy();

    expect($policy->update($user, $board))->toBeFalse();
});

test('admin can delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_ADMIN]);

    $policy = new BoardPolicy();

    expect($policy->delete($user, $board))->toBeTrue();
});

test('non-admin member cannot delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);

    $policy = new BoardPolicy();

    expect($policy->delete($user, $board))->toBeFalse();
});

test('non-member cannot delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();

    $policy = new BoardPolicy();

    expect($policy->delete($user, $board))->toBeFalse();
});

test('admin can restore a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_ADMIN]);

    $policy = new BoardPolicy();

    expect($policy->restore($user, $board))->toBeTrue();
});

test('non-admin cannot restore a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);

    $policy = new BoardPolicy();

    expect($policy->restore($user, $board))->toBeFalse();
});

test('admin can force delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_ADMIN]);

    $policy = new BoardPolicy();

    expect($policy->forceDelete($user, $board))->toBeTrue();
});

test('non-admin cannot force delete a board', function (): void {
    $user = User::factory()->create();
    $board = Board::factory()->create();
    $board->users()->attach($user, ['role' => Board::ROLE_USER]);

    $policy = new BoardPolicy();

    expect($policy->forceDelete($user, $board))->toBeFalse();
});
