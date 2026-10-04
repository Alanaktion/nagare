<?php

use App\Enums\BoardRole;
use App\Enums\SprintCycle;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function boardPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Roadmap',
        'has_stories' => false,
        'has_sprints' => false,
        'statuses' => [
            ['name' => 'To Do'],
            ['name' => 'Done', 'is_closed' => true],
        ],
    ], $overrides);
}

test('guests are redirected to login', function () {
    $board = Board::factory()->create();

    $this->get(route('boards.index'))->assertRedirect(route('login'));
    $this->post(route('boards.store'), boardPayload())->assertRedirect(route('login'));
    $this->get(route('boards.show', $board))->assertRedirect(route('login'));
});

test('index lists only boards the user belongs to', function () {
    $user = User::factory()->create();
    $mine = Board::factory()->withMember($user)->create();
    Board::factory()->create();

    $this->actingAs($user)->get(route('boards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Index')
            ->has('boards.data', 1)
            ->where('boards.data.0.id', $mine->id)
            ->where('boards.data.0.role', 'member')
            ->where('sidebarBoards', [['id' => $mine->id, 'name' => $mine->name]]));
});

test('sidebar boards are shared on every page', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withMember($user)->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebarBoards', [['id' => $board->id, 'name' => $board->name]]));
});

test('creating a board adds statuses and makes the creator an admin', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('boards.store'), boardPayload());

    $board = Board::firstOrFail();
    $response->assertRedirect(route('boards.show', $board));
    expect($board->created_by)->toBe($user->id)
        ->and($board->roleFor($user))->toBe(BoardRole::Admin)
        ->and($board->statuses->pluck('name', 'sort')->all())->toBe([0 => 'To Do', 1 => 'Done'])
        ->and($board->statuses->pluck('is_closed')->all())->toBe([false, true]);
});

test('all combinations of stories and sprints are supported', function (bool $stories, bool $sprints) {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('boards.store'), boardPayload([
        'has_stories' => $stories,
        'has_sprints' => $sprints,
        'sprint_cycle' => $sprints ? 'monthly' : null,
    ]))->assertSessionHasNoErrors();

    $board = Board::firstOrFail();
    expect($board->has_stories)->toBe($stories)
        ->and($board->has_sprints)->toBe($sprints)
        ->and($board->sprint_cycle)->toBe($sprints ? SprintCycle::Monthly : null);
})->with([[false, false], [true, false], [false, true], [true, true]]);

test('sprint cycle is required with sprints and discarded without', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('boards.store'), boardPayload(['has_sprints' => true]))
        ->assertSessionHasErrors('sprint_cycle');

    $this->actingAs($user)->post(route('boards.store'), boardPayload(['sprint_cycle' => 'weekly']))
        ->assertSessionHasNoErrors();
    expect(Board::firstOrFail()->sprint_cycle)->toBeNull();
});

test('board creation validates name and statuses', function (array $overrides, string $errorKey) {
    $this->actingAs(User::factory()->create())
        ->post(route('boards.store'), boardPayload($overrides))
        ->assertSessionHasErrors($errorKey);

    expect(Board::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'no statuses' => [['statuses' => []], 'statuses'],
    'unnamed status' => [['statuses' => [['name' => '']]], 'statuses.0.name'],
    'status id on create' => [['statuses' => [['id' => 1, 'name' => 'x']]], 'statuses.0.id'],
]);

test('members can view and edit a board', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withDefaultStatuses()->withMember($user)->create();

    $this->actingAs($user)->get(route('boards.show', $board))
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Show')
            ->has('board.data.statuses', 3));
    $this->actingAs($user)->get(route('boards.edit', $board))->assertOk();
});

test('non-members cannot view, edit, update or delete a board', function () {
    $outsider = User::factory()->create();
    $board = Board::factory()->withDefaultStatuses()->create();

    $this->actingAs($outsider);
    $this->get(route('boards.show', $board))->assertForbidden();
    $this->get(route('boards.edit', $board))->assertForbidden();
    $this->put(route('boards.update', $board), boardPayload())->assertForbidden();
    $this->delete(route('boards.destroy', $board))->assertForbidden();
});

test('updating a board syncs statuses by position', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withDefaultStatuses()->withMember($user)->create();
    [$todo, $progress, $done] = $board->statuses;

    $this->actingAs($user)->put(route('boards.update', $board), boardPayload([
        'name' => 'Renamed',
        'has_stories' => true,
        'statuses' => [
            ['id' => $done->id, 'name' => 'Shipped', 'is_closed' => true],
            ['id' => $todo->id, 'name' => 'Backlog'],
            ['name' => 'Review'],
        ],
    ]))->assertSessionHasNoErrors()->assertRedirect(route('boards.show', $board));

    $board->refresh();
    expect($board->name)->toBe('Renamed')
        ->and($board->has_stories)->toBeTrue()
        ->and($board->statuses->pluck('name')->all())->toBe(['Shipped', 'Backlog', 'Review'])
        ->and($board->statuses->first()->id)->toBe($done->id)
        ->and($progress->fresh()->trashed())->toBeTrue();
});

test('statuses from another board cannot be edited', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withDefaultStatuses()->withMember($user)->create();
    $foreign = Board::factory()->withDefaultStatuses()->create()->statuses->first();

    $this->actingAs($user)->put(route('boards.update', $board), boardPayload([
        'statuses' => [['id' => $foreign->id, 'name' => 'Hijacked']],
    ]))->assertSessionHasErrors('statuses.0.id');

    expect($foreign->fresh()->name)->not->toBe('Hijacked');
});

test('only admins can delete a board', function () {
    $member = User::factory()->create();
    $admin = User::factory()->create();
    $board = Board::factory()->withMember($member)->withAdmin($admin)->create();

    $this->actingAs($member)->delete(route('boards.destroy', $board))->assertForbidden();
    expect($board->fresh()->trashed())->toBeFalse();

    $this->actingAs($admin)->delete(route('boards.destroy', $board))->assertRedirect(route('boards.index'));
    expect($board->fresh()->trashed())->toBeTrue();
});

test('admins can restore a deleted board and members cannot', function () {
    $member = User::factory()->create();
    $admin = User::factory()->create();
    $board = Board::factory()->withMember($member)->withAdmin($admin)->create();
    $board->delete();

    $this->actingAs($admin)->get(route('boards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('boards.data', 0)
            ->has('archivedBoards.data', 1));
    $this->actingAs($member)->get(route('boards.index'))
        ->assertInertia(fn (Assert $page) => $page->has('archivedBoards.data', 0));

    $this->actingAs($member)->post(route('boards.restore', $board))->assertForbidden();
    $this->actingAs($admin)->post(route('boards.restore', $board))->assertRedirect(route('boards.show', $board));
    expect($board->fresh()->trashed())->toBeFalse();
});

test('deleted boards are not viewable', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withAdmin($user)->create();
    $board->delete();

    $this->actingAs($user)->get(route('boards.show', $board))->assertNotFound();
});

describe('removing statuses that have issues', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
        [$this->todo, $this->progress, $this->done] = $this->board->statuses;
        $this->issue = Issue::factory()->inStatus($this->progress)->create();
    });

    function keepOnly(array $statuses, array $extra = []): array
    {
        return boardPayload([
            'statuses' => collect($statuses)->map(fn ($status) => [
                'id' => $status->id, 'name' => $status->name, 'is_closed' => $status->is_closed,
            ])->all(),
            ...$extra,
        ]);
    }

    test('a target status is required', function () {
        $this->actingAs($this->user)
            ->put(route('boards.update', $this->board), keepOnly([$this->todo, $this->done]))
            ->assertSessionHasErrors("status_moves.{$this->progress->id}");

        expect($this->progress->fresh()->trashed())->toBeFalse();
    });

    test('the target must be a status that is kept', function () {
        $this->actingAs($this->user)
            ->put(route('boards.update', $this->board), keepOnly([$this->todo, $this->done], [
                'status_moves' => [$this->progress->id => $this->progress->id],
            ]))->assertSessionHasErrors("status_moves.{$this->progress->id}");
    });

    test('issues move to the target status and pick up its closed state', function () {
        $this->actingAs($this->user)
            ->put(route('boards.update', $this->board), keepOnly([$this->todo, $this->done], [
                'status_moves' => [$this->progress->id => $this->done->id],
            ]))->assertSessionHasNoErrors();

        expect($this->progress->fresh()->trashed())->toBeTrue()
            ->and($this->issue->fresh()->status_id)->toBe($this->done->id)
            ->and($this->issue->fresh()->closed_at)->not->toBeNull();
    });

    test('empty statuses can be removed without a target', function () {
        $this->actingAs($this->user)
            ->put(route('boards.update', $this->board), keepOnly([$this->todo, $this->progress]))
            ->assertSessionHasNoErrors();

        expect($this->done->fresh()->trashed())->toBeTrue();
    });

    test('toggling a closing status closes or reopens its issues', function () {
        $this->actingAs($this->user);
        $payload = fn (bool $closed) => boardPayload(['statuses' => [
            ['id' => $this->todo->id, 'name' => 'To Do'],
            ['id' => $this->progress->id, 'name' => 'In Progress', 'is_closed' => $closed],
            ['id' => $this->done->id, 'name' => 'Done', 'is_closed' => true],
        ]]);

        $this->put(route('boards.update', $this->board), $payload(true));
        expect($this->issue->fresh()->closed_at)->not->toBeNull();

        $this->put(route('boards.update', $this->board), $payload(false));
        expect($this->issue->fresh()->closed_at)->toBeNull();
    });
});

test('the edit page tells the form how many issues each status has', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withDefaultStatuses()->withMember($user)->create();
    Issue::factory()->inStatus($board->statuses[1])->count(2)->create();

    $this->actingAs($user)->get(route('boards.edit', $board))
        ->assertInertia(fn (Assert $page) => $page
            ->where('board.data.statuses.0.issues_count', 0)
            ->where('board.data.statuses.1.issues_count', 2));
});
