<?php

use App\Enums\BoardRole;
use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create(['name' => 'Ada Admin']);
    $this->member = User::factory()->create(['name' => 'Mia Member']);
    $this->board = Board::factory()->withDefaultStatuses()->withAdmin($this->admin)->withMember($this->member)->create();
});

test('guests are redirected to login', function () {
    $this->post(route('boards.members.store', $this->board), [])->assertRedirect(route('login'));
    $this->put(route('boards.members.update', [$this->board, $this->member]), [])->assertRedirect(route('login'));
    $this->delete(route('boards.members.destroy', [$this->board, $this->member]))->assertRedirect(route('login'));
});

test('the edit page lists members with their roles', function () {
    $this->actingAs($this->member)->get(route('boards.edit', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->has('members.data', 2)
            ->where('members.data.0.name', 'Ada Admin')
            ->where('members.data.0.role', 'admin')
            ->where('members.data.1.role', 'member')
            ->missing('candidates'));
});

test('board pages tell the current user their own role', function () {
    foreach (['boards.edit' => $this->admin, 'boards.show' => $this->member] as $route => $user) {
        $this->actingAs($user)->get(route($route, $this->board))
            ->assertInertia(fn (Assert $page) => $page->where('board.data.role', $this->board->roleFor($user)->value));
    }
});

describe('finding people to add', function () {
    test('admins can search users who are not on the board yet', function () {
        $outsider = User::factory()->create(['name' => 'Olive Outsider']);
        User::factory()->create(['name' => 'Zed Other']);

        $this->actingAs($this->admin)->get(route('boards.edit', $this->board).'?search=olive')
            ->assertInertia(fn (Assert $page) => $page->reloadOnly('candidates', fn (Assert $reload) => $reload
                ->has('candidates.data', 1)
                ->where('candidates.data.0.id', $outsider->id)));
    });

    test('the search matches email addresses and excludes current members', function () {
        $outsider = User::factory()->create(['email' => 'findme@example.test']);

        $this->actingAs($this->admin)->get(route('boards.edit', $this->board).'?search=findme@')
            ->assertInertia(fn (Assert $page) => $page->reloadOnly('candidates', fn (Assert $reload) => $reload
                ->has('candidates.data', 1)
                ->where('candidates.data.0.id', $outsider->id)));

        $this->actingAs($this->admin)->get(route('boards.edit', $this->board).'?search=Mia')
            ->assertInertia(fn (Assert $page) => $page->reloadOnly('candidates', fn (Assert $reload) => $reload->has('candidates.data', 0)));
    });

    test('regular members get no candidates', function () {
        User::factory()->create(['name' => 'Olive Outsider']);

        $this->actingAs($this->member)->get(route('boards.edit', $this->board))
            ->assertInertia(fn (Assert $page) => $page->reloadOnly('candidates', fn (Assert $reload) => $reload->where('candidates', [])));
    });
});

describe('adding members', function () {
    test('admins can add an existing user with a role', function () {
        Event::fake([BoardUpdated::class]);
        $newcomer = User::factory()->create();

        $this->actingAs($this->admin)->post(route('boards.members.store', $this->board), ['user_id' => $newcomer->id, 'role' => 'member'])
            ->assertSessionHasNoErrors()->assertRedirect();

        expect($this->board->roleFor($newcomer))->toBe(BoardRole::Member);
        Event::assertDispatched(BoardUpdated::class);

        $this->actingAs($newcomer)->get(route('boards.show', $this->board))->assertOk();
    });

    test('a user can be added as an admin', function () {
        $newcomer = User::factory()->create();

        $this->actingAs($this->admin)->post(route('boards.members.store', $this->board), ['user_id' => $newcomer->id, 'role' => 'admin']);

        expect($this->board->roleFor($newcomer))->toBe(BoardRole::Admin);
    });

    test('regular members and outsiders cannot add members', function () {
        $newcomer = User::factory()->create();

        foreach ([$this->member, User::factory()->create()] as $actor) {
            $this->actingAs($actor)->post(route('boards.members.store', $this->board), ['user_id' => $newcomer->id, 'role' => 'member'])->assertForbidden();
        }
        expect($this->board->roleFor($newcomer))->toBeNull();
    });

    test('the user must exist, not already be a member, and the role must be valid', function (callable $payload, string $errorKey) {
        $this->actingAs($this->admin)->post(route('boards.members.store', $this->board), $payload($this))->assertSessionHasErrors($errorKey);
    })->with([
        'unknown user' => [fn () => ['user_id' => 9999, 'role' => 'member'], 'user_id'],
        'already a member' => [fn ($test) => ['user_id' => $test->member->id, 'role' => 'member'], 'user_id'],
        'invalid role' => [fn () => ['user_id' => User::factory()->create()->id, 'role' => 'owner'], 'role'],
    ]);
});

describe('changing roles', function () {
    test('admins can promote and demote members', function () {
        $this->actingAs($this->admin)->put(route('boards.members.update', [$this->board, $this->member]), ['role' => 'admin'])
            ->assertSessionHasNoErrors();
        expect($this->board->roleFor($this->member))->toBe(BoardRole::Admin);

        $this->put(route('boards.members.update', [$this->board, $this->member]), ['role' => 'member']);
        expect($this->board->roleFor($this->member))->toBe(BoardRole::Member);
    });

    test('the last admin cannot be demoted', function () {
        $this->actingAs($this->admin)->put(route('boards.members.update', [$this->board, $this->admin]), ['role' => 'member'])
            ->assertSessionHasErrors('role');

        expect($this->board->roleFor($this->admin))->toBe(BoardRole::Admin);
    });

    test('an admin can step down when another admin exists', function () {
        $this->board->users()->updateExistingPivot($this->member->id, ['role' => 'admin']);

        $this->actingAs($this->admin)->put(route('boards.members.update', [$this->board, $this->admin]), ['role' => 'member'])
            ->assertSessionHasNoErrors();

        expect($this->board->roleFor($this->admin))->toBe(BoardRole::Member);
    });

    test('regular members cannot change roles, and only members have roles', function () {
        $this->actingAs($this->member)->put(route('boards.members.update', [$this->board, $this->member]), ['role' => 'admin'])->assertForbidden();
        expect($this->board->roleFor($this->member))->toBe(BoardRole::Member);

        $this->actingAs($this->admin)->put(route('boards.members.update', [$this->board, User::factory()->create()]), ['role' => 'member'])->assertNotFound();
    });
});

describe('removing members', function () {
    test('admins can remove a member, which unassigns their issues and ends their access', function () {
        $assigned = Issue::factory()->inStatus($this->board->statuses[0])->create(['assigned_id' => $this->member->id]);
        $unrelated = Issue::factory()->inStatus($this->board->statuses[0])->create(['assigned_id' => $this->admin->id]);
        $otherBoard = Issue::factory()->create(['assigned_id' => $this->member->id]);

        $this->actingAs($this->admin)->delete(route('boards.members.destroy', [$this->board, $this->member]))
            ->assertSessionHasNoErrors()->assertRedirect();

        expect($this->board->roleFor($this->member))->toBeNull()
            ->and($assigned->fresh()->assigned_id)->toBeNull()
            ->and($unrelated->fresh()->assigned_id)->toBe($this->admin->id)
            ->and($otherBoard->fresh()->assigned_id)->toBe($this->member->id);

        $this->actingAs($this->member)->get(route('boards.show', $this->board))->assertForbidden();
    });

    test('members can leave a board but cannot remove others', function () {
        $other = User::factory()->create();
        $this->board->users()->attach($other, ['role' => 'member']);

        $this->actingAs($this->member)->delete(route('boards.members.destroy', [$this->board, $other]))->assertForbidden();

        $this->delete(route('boards.members.destroy', [$this->board, $this->member]))->assertRedirect(route('boards.index'));
        expect($this->board->roleFor($this->member))->toBeNull()->and($this->board->roleFor($other))->not->toBeNull();
    });

    test('the last admin cannot be removed or leave', function () {
        $this->actingAs($this->admin)->delete(route('boards.members.destroy', [$this->board, $this->admin]))
            ->assertSessionHasErrors('user');

        expect($this->board->roleFor($this->admin))->toBe(BoardRole::Admin);
    });

    test('outsiders cannot remove anyone', function () {
        $this->actingAs(User::factory()->create())->delete(route('boards.members.destroy', [$this->board, $this->member]))->assertForbidden();

        expect($this->board->roleFor($this->member))->not->toBeNull();
    });

    test('someone who is not a member cannot be removed', function () {
        $this->actingAs($this->admin)->delete(route('boards.members.destroy', [$this->board, User::factory()->create()]))->assertNotFound();
    });
});
