<?php

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('deleting an account hands boards to another member instead of leaving them without an admin', function () {
    $user = User::factory()->create();
    $longest = User::factory()->create();
    $newer = User::factory()->create();
    $otherAdmin = User::factory()->create();

    $shared = Board::factory()->withAdmin($user)->withMember($longest)->create();
    $this->travel(1)->minute();
    $shared->users()->attach($newer, ['role' => BoardRole::Member->value]);
    $solo = Board::factory()->withAdmin($user)->create();
    $coAdmined = Board::factory()->withAdmin($user)->withAdmin($otherAdmin)->withMember($longest)->create();

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasNoErrors();

    expect($shared->roleFor($longest))->toBe(BoardRole::Admin)
        ->and($shared->roleFor($newer))->toBe(BoardRole::Member)
        ->and($solo->fresh()->trashed())->toBeTrue()
        ->and($coAdmined->roleFor($longest))->toBe(BoardRole::Member)
        ->and($coAdmined->fresh()->trashed())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
