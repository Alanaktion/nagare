<?php

use App\Enums\SprintCycle;
use App\Models\Board;
use App\Models\Issue;
use App\Models\Sprint;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-07 12:00:00');
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withSprints(SprintCycle::Weekly)->withDefaultStatuses()->withMember($this->user)->create();
    $this->actingAs($this->user);
});

test('a board with sprints opens on the current sprint', function () {
    $sprint = Sprint::factory()->for($this->board)->current()->create();

    $this->get(route('boards.show', $this->board))
        ->assertRedirect(route('boards.sprints.show', [$this->board, $sprint]));
});

test('the current sprint is created from the cycle when missing', function () {
    $this->get(route('boards.show', $this->board))
        ->assertRedirect(route('boards.sprints.show', [$this->board, '2026W41']));

    $sprint = $this->board->sprints()->sole();
    expect($sprint->slug)->toBe('2026W41')
        ->and($sprint->start_date->toDateString())->toBe('2026-10-05')
        ->and($sprint->end_date->toDateString())->toBe('2026-10-11');

    $this->get(route('boards.show', $this->board));
    expect($this->board->sprints()->count())->toBe(1);
});

test('a custom-cycle board without a current sprint opens on the backlog', function () {
    $this->board->update(['sprint_cycle' => SprintCycle::Custom]);

    $this->get(route('boards.show', $this->board))->assertRedirect(route('boards.backlog', $this->board));
    expect($this->board->sprints()->count())->toBe(0);
});

test('a closed current sprint is not replaced', function () {
    Sprint::factory()->for($this->board)->between(today()->startOfWeek(), today()->endOfWeek())->closed()->create(['slug' => '2026W41']);

    $this->get(route('boards.show', $this->board))->assertRedirect(route('boards.backlog', $this->board));
    expect($this->board->sprints()->count())->toBe(1);
});

test('the sprint view only shows that sprint\'s issues and its neighbours', function () {
    $previous = Sprint::factory()->for($this->board)->between(today()->subDays(20), today()->subDays(10))->closed()->create();
    $sprint = Sprint::factory()->for($this->board)->current()->create();
    $next = Sprint::factory()->for($this->board)->between(today()->addDays(11), today()->addDays(20))->create();
    $inSprint = Issue::factory()->inStatus($this->board->statuses[0])->create(['sprint_id' => $sprint->id]);
    Issue::factory()->inStatus($this->board->statuses[0])->create(['sprint_id' => $next->id]);
    Issue::factory()->inStatus($this->board->statuses[0])->create();

    $this->get(route('boards.sprints.show', [$this->board, $sprint]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Show')
            ->where('sprint.data.slug', $sprint->slug)
            ->has('issues.data', 1)
            ->where('issues.data.0.id', $inSprint->id)
            ->where('issues.data.0.sprint_id', $sprint->id)
            ->where('previousSprint.data.id', $previous->id)
            ->where('nextSprint.data.id', $next->id)
            ->has('sprints.data', 3)
            ->has('openSprints.data', 2));
});

test('the backlog shows only issues without a sprint', function () {
    $sprint = Sprint::factory()->for($this->board)->current()->create();
    Issue::factory()->inStatus($this->board->statuses[0])->create(['sprint_id' => $sprint->id]);
    $unplanned = Issue::factory()->inStatus($this->board->statuses[0])->create();

    $this->get(route('boards.backlog', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sprint', null)
            ->has('issues.data', 1)
            ->where('issues.data.0.id', $unplanned->id));
});

test('non-members cannot see sprints', function () {
    $sprint = Sprint::factory()->for($this->board)->current()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('boards.sprints.show', [$this->board, $sprint]))->assertForbidden();
    $this->get(route('boards.backlog', $this->board))->assertForbidden();
});

test('boards without sprints have no sprint or backlog pages', function () {
    $plain = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    $foreign = Sprint::factory()->for($plain)->create();

    $this->get(route('boards.sprints.show', [$plain, $foreign]))->assertNotFound();
    $this->get(route('boards.backlog', $plain))->assertNotFound();
});

test('a sprint can only be opened through its own board', function () {
    $other = Sprint::factory()->create();

    $this->get(route('boards.sprints.show', [$this->board, $other->slug]))->assertNotFound();
});

test('boards without sprints show every issue', function () {
    $plain = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    Issue::factory()->inStatus($plain->statuses[0])->count(2)->create();

    $this->get(route('boards.show', $plain))
        ->assertInertia(fn (Assert $page) => $page->has('issues.data', 2)->where('sprint', null)->has('sprints.data', 0));
});

describe('creating sprints manually', function () {
    test('a fixed-cycle sprint covers the period containing the date', function () {
        $this->post(route('boards.sprints.store', $this->board), ['start_date' => '2026-10-14'])
            ->assertRedirect(route('boards.sprints.show', [$this->board, '2026W42']));

        $sprint = $this->board->sprints()->sole();
        expect($sprint->start_date->toDateString())->toBe('2026-10-12')
            ->and($sprint->end_date->toDateString())->toBe('2026-10-18');
    });

    test('a period can only have one sprint', function () {
        $this->post(route('boards.sprints.store', $this->board), ['start_date' => '2026-10-14']);

        $this->post(route('boards.sprints.store', $this->board), ['start_date' => '2026-10-16'])
            ->assertSessionHasErrors('start_date');
        expect($this->board->sprints()->count())->toBe(1);
    });

    test('a custom sprint needs an end date that is not before the start', function () {
        $this->board->update(['sprint_cycle' => SprintCycle::Custom]);

        $this->post(route('boards.sprints.store', $this->board), ['start_date' => '2026-10-14'])
            ->assertSessionHasErrors('end_date');
        $this->post(route('boards.sprints.store', $this->board), ['start_date' => '2026-10-14', 'end_date' => '2026-10-10'])
            ->assertSessionHasErrors('end_date');

        $this->post(route('boards.sprints.store', $this->board), ['start_date' => '2026-10-14', 'end_date' => '2026-10-30'])
            ->assertSessionHasNoErrors();
        $sprint = $this->board->sprints()->sole();
        expect($sprint->slug)->toBe('2026-10-14')->and($sprint->end_date->toDateString())->toBe('2026-10-30');
    });

    test('only members of boards with sprints can create them', function () {
        $plain = Board::factory()->withMember($this->user)->create();

        $this->post(route('boards.sprints.store', $plain), ['start_date' => '2026-10-14'])->assertForbidden();

        $this->actingAs(User::factory()->create());
        $this->post(route('boards.sprints.store', $this->board), ['start_date' => '2026-10-14'])->assertForbidden();
        expect(Sprint::count())->toBe(0);
    });
});

describe('closing sprints', function () {
    beforeEach(function () {
        $this->sprint = Sprint::factory()->for($this->board)->current()->create();
        $this->open = Issue::factory()->inStatus($this->board->statuses[0])->create(['sprint_id' => $this->sprint->id]);
        $this->done = Issue::factory()->inStatus($this->board->statuses[2])->create(['sprint_id' => $this->sprint->id]);
    });

    test('unfinished issues go back to the backlog and finished ones stay', function () {
        $this->post(route('boards.sprints.closed.store', [$this->board, $this->sprint]))->assertRedirect();

        expect($this->sprint->fresh()->isClosed())->toBeTrue()
            ->and($this->open->fresh()->sprint_id)->toBeNull()
            ->and($this->done->fresh()->sprint_id)->toBe($this->sprint->id);
    });

    test('unfinished issues can move to another open sprint', function () {
        $next = Sprint::factory()->for($this->board)->between(today()->addDays(11), today()->addDays(20))->create();

        $this->post(route('boards.sprints.closed.store', [$this->board, $this->sprint]), ['move_to' => $next->id]);

        expect($this->open->fresh()->sprint_id)->toBe($next->id)
            ->and($this->done->fresh()->sprint_id)->toBe($this->sprint->id);
    });

    test('issues cannot move to a closed sprint, another board\'s sprint, or the same sprint', function () {
        $closed = Sprint::factory()->for($this->board)->between(today()->subDays(30), today()->subDays(20))->closed()->create();
        $foreign = Sprint::factory()->create();

        foreach ([$closed->id, $foreign->id, $this->sprint->id] as $target) {
            $this->post(route('boards.sprints.closed.store', [$this->board, $this->sprint]), ['move_to' => $target])
                ->assertSessionHasErrors('move_to');
        }
        expect($this->sprint->fresh()->isClosed())->toBeFalse();
    });

    test('closing twice does nothing the second time', function () {
        $this->post(route('boards.sprints.closed.store', [$this->board, $this->sprint]));
        $closedAt = $this->sprint->fresh()->closed_at;
        $this->open->refresh()->update(['sprint_id' => $this->sprint->id]);

        $this->travel(1)->hour();
        $this->post(route('boards.sprints.closed.store', [$this->board, $this->sprint]))->assertRedirect();

        expect($this->sprint->fresh()->closed_at->equalTo($closedAt))->toBeTrue()
            ->and($this->open->fresh()->sprint_id)->toBe($this->sprint->id);
    });

    test('non-members cannot close sprints', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('boards.sprints.closed.store', [$this->board, $this->sprint]))->assertForbidden();
        expect($this->sprint->fresh()->isClosed())->toBeFalse();
    });
});

describe('assigning issues to sprints', function () {
    beforeEach(function () {
        $this->sprint = Sprint::factory()->for($this->board)->current()->create();
        $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    });

    test('new and existing issues can be put in an open sprint', function () {
        $this->post(route('boards.issues.store', $this->board), ['name' => 'Planned', 'role' => 'task', 'sprint_id' => $this->sprint->id])
            ->assertSessionHasNoErrors();
        $this->put(route('issues.update', $this->issue), ['sprint_id' => $this->sprint->id])->assertSessionHasNoErrors();

        expect(Issue::where('name', 'Planned')->sole()->sprint_id)->toBe($this->sprint->id)
            ->and($this->issue->fresh()->sprint_id)->toBe($this->sprint->id);

        $this->put(route('issues.update', $this->issue), ['sprint_id' => null])->assertSessionHasNoErrors();
        expect($this->issue->fresh()->sprint_id)->toBeNull();
    });

    test('closed sprints and other boards\' sprints are rejected', function () {
        $closed = Sprint::factory()->for($this->board)->between(today()->subDays(30), today()->subDays(20))->closed()->create();
        $foreign = Sprint::factory()->create();

        foreach ([$closed->id, $foreign->id] as $target) {
            $this->put(route('issues.update', $this->issue), ['sprint_id' => $target])->assertSessionHasErrors('sprint_id');
        }
    });

    test('boards without sprints reject a sprint', function () {
        $plain = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();

        $this->post(route('boards.issues.store', $plain), ['name' => 'x', 'role' => 'task', 'sprint_id' => $this->sprint->id])
            ->assertSessionHasErrors('sprint_id');
    });

    test('the issue page offers open sprints plus the issue\'s own closed sprint', function () {
        $closed = Sprint::factory()->for($this->board)->between(today()->subDays(30), today()->subDays(20))->closed()->create();
        Sprint::factory()->for($this->board)->between(today()->subDays(60), today()->subDays(40))->closed()->create();
        $this->issue->update(['sprint_id' => $closed->id]);

        $this->get(route('issues.show', $this->issue))
            ->assertInertia(fn (Assert $page) => $page
                ->has('openSprints.data', 2)
                ->where('openSprints.data', fn ($sprints) => collect($sprints)->pluck('id')->sort()->values()->all() === collect([$closed->id, $this->sprint->id])->sort()->values()->all()));
    });
});

describe('rolling sprints', function () {
    test('creates today\'s sprint and carries unfinished issues out of ended sprints', function () {
        $ended = Sprint::factory()->for($this->board)->between(today()->subDays(10), today()->subDays(4))->create();
        $open = Issue::factory()->inStatus($this->board->statuses[0])->create(['sprint_id' => $ended->id]);
        $done = Issue::factory()->inStatus($this->board->statuses[2])->create(['sprint_id' => $ended->id]);

        $this->artisan('sprints:roll')->assertSuccessful();

        $current = $this->board->sprints()->where('slug', '2026W41')->sole();
        expect($ended->fresh()->isClosed())->toBeTrue()
            ->and($current->isClosed())->toBeFalse()
            ->and($open->fresh()->sprint_id)->toBe($current->id)
            ->and($done->fresh()->sprint_id)->toBe($ended->id);
    });

    test('running again changes nothing', function () {
        $this->artisan('sprints:roll');
        $this->artisan('sprints:roll');

        expect($this->board->sprints()->count())->toBe(1);
    });

    test('custom-cycle and sprint-less boards are left alone', function () {
        $custom = Board::factory()->withSprints(SprintCycle::Custom)->create();
        $ended = Sprint::factory()->for($custom)->between(today()->subDays(10), today()->subDays(4))->create();
        $plain = Board::factory()->create();

        $this->artisan('sprints:roll')->assertSuccessful();

        expect($ended->fresh()->isClosed())->toBeFalse()
            ->and($custom->sprints()->count())->toBe(1)
            ->and($plain->sprints()->count())->toBe(0);
    });
});

test('the newest overlapping sprint is the current one', function () {
    Sprint::factory()->for($this->board)->between(today()->subDays(5), today()->addDays(5))->create();
    $newer = Sprint::factory()->for($this->board)->between(today()->subDays(1), today()->addDays(9))->create();

    expect($this->board->currentSprint()->is($newer))->toBeTrue();
});

test('a sprint is current on its first and last day', function () {
    $sprint = Sprint::factory()->for($this->board)->between(today(), today()->addDays(6))->create();

    expect($this->board->currentSprint()->is($sprint))->toBeTrue();

    $this->travelTo(today()->addDays(6)->setTime(23, 59));
    expect($this->board->currentSprint()->is($sprint))->toBeTrue();

    $this->travelTo(today()->addDay());
    expect($this->board->currentSprint())->toBeNull();
});

describe('stories on sprint boards', function () {
    beforeEach(function () {
        $this->board->update(['has_stories' => true]);
        $this->status = $this->board->statuses[0];
        $this->sprint = Sprint::factory()->for($this->board)->current()->create();
        $this->other = Sprint::factory()->for($this->board)->between(today()->addDays(11), today()->addDays(20))->create();

        $this->story = fn (array $attributes = []) => Issue::factory()->story()->inStatus($this->status)->create($attributes);
        $this->task = fn (Issue $story, ?Sprint $sprint) => Issue::factory()->inStatus($this->status)->create(['parent_id' => $story->id, 'sprint_id' => $sprint?->id]);
        $this->idsIn = fn (string $route, array $params) => collect($this->get(route($route, $params))->inertiaProps('issues.data'))->pluck('id')->sort()->values()->all();
    });

    test('a story appears in every sprint that has one of its tasks, with only those tasks', function () {
        $story = ($this->story)();
        $inSprint = ($this->task)($story, $this->sprint);
        $inOther = ($this->task)($story, $this->other);

        expect(($this->idsIn)('boards.sprints.show', [$this->board, $this->sprint]))->toBe([$story->id, $inSprint->id])
            ->and(($this->idsIn)('boards.sprints.show', [$this->board, $this->other]))->toBe([$story->id, $inOther->id]);
    });

    test('a story assigned to a sprint appears there even without tasks in it', function () {
        $story = ($this->story)(['sprint_id' => $this->sprint->id]);
        ($this->task)($story, $this->other);

        expect(($this->idsIn)('boards.sprints.show', [$this->board, $this->sprint]))->toBe([$story->id]);
    });

    test('a story does not appear in sprints where it has no tasks and is not assigned', function () {
        $story = ($this->story)();
        ($this->task)($story, $this->other);

        expect(($this->idsIn)('boards.sprints.show', [$this->board, $this->sprint]))->toBe([]);
    });

    test('a deleted task does not pull its story into a sprint', function () {
        $story = ($this->story)();
        ($this->task)($story, $this->sprint)->delete();

        expect(($this->idsIn)('boards.sprints.show', [$this->board, $this->sprint]))->toBe([]);
    });

    test('the backlog shows stories with backlog tasks and fresh stories, but not fully planned ones', function () {
        $withBacklogTask = ($this->story)();
        $backlogTask = ($this->task)($withBacklogTask, null);
        ($this->task)($withBacklogTask, $this->sprint);
        $fresh = ($this->story)();
        $fullyPlanned = ($this->story)();
        ($this->task)($fullyPlanned, $this->sprint);
        $assignedElsewhere = ($this->story)(['sprint_id' => $this->sprint->id]);

        expect(($this->idsIn)('boards.backlog', [$this->board]))
            ->toBe(collect([$withBacklogTask->id, $backlogTask->id, $fresh->id])->sort()->values()->all())
            ->not->toContain($fullyPlanned->id, $assignedElsewhere->id);
    });

    test('stories report how many tasks they have in total', function () {
        $story = ($this->story)();
        ($this->task)($story, $this->sprint);
        ($this->task)($story, $this->other);
        ($this->task)($story, null)->delete();

        $this->get(route('boards.sprints.show', [$this->board, $this->sprint]))
            ->assertInertia(fn (Assert $page) => $page->where('issues.data', fn ($issues) => collect($issues)->firstWhere('id', $story->id)['children_count'] === 2));
    });

    test('boards without stories are unaffected', function () {
        $this->board->update(['has_stories' => false]);
        $task = Issue::factory()->inStatus($this->status)->create(['sprint_id' => $this->sprint->id]);

        expect(($this->idsIn)('boards.sprints.show', [$this->board, $this->sprint]))->toBe([$task->id]);
    });
});
