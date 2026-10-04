<?php

use App\Models\Board;
use App\Models\Issue;
use App\Models\Sprint;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard is empty for a user with no boards', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('boards.data', 0)
            ->loadDeferredProps(fn (Assert $deferred) => $deferred
                ->has('assignedIssues.data', 0)
                ->has('sprintSummaries', 0)));
});

test('recent boards are the most recently active boards the user belongs to', function () {
    $user = User::factory()->create();
    $quiet = Board::factory()->withDefaultStatuses()->withMember($user)->create(['name' => 'Quiet']);
    $busy = Board::factory()->withDefaultStatuses()->withMember($user)->create(['name' => 'Busy']);
    $untouched = Board::factory()->withMember($user)->create(['name' => 'Untouched']);
    Board::factory()->withDefaultStatuses()->create();

    $this->travelTo(now()->subDay());
    Issue::factory()->inStatus($quiet->statuses[0])->create();
    $this->travelBack();
    Issue::factory()->inStatus($busy->statuses[0])->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('boards.data', fn ($boards) => collect($boards)->pluck('name')->all() === ['Busy', 'Quiet', 'Untouched']));
});

test('only six recent boards are shown', function () {
    $user = User::factory()->create();
    Board::factory()->count(8)->withMember($user)->create();

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->has('boards.data', 6));
});

test('assigned issues are the user\'s open issues on their boards, newest first', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withDefaultStatuses()->withMember($user)->create();
    $older = Issue::factory()->inStatus($board->statuses[0])->create(['assigned_id' => $user->id, 'updated_at' => now()->subDay()]);
    $newer = Issue::factory()->inStatus($board->statuses[1])->create(['assigned_id' => $user->id]);
    Issue::factory()->inStatus($board->statuses[2])->create(['assigned_id' => $user->id]);
    Issue::factory()->inStatus($board->statuses[0])->create(['assigned_id' => User::factory()->create()->id]);
    $removedFrom = Board::factory()->withDefaultStatuses()->create();
    Issue::factory()->inStatus($removedFrom->statuses[0])->create(['assigned_id' => $user->id]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('assignedIssues')
            ->loadDeferredProps(fn (Assert $deferred) => $deferred
                ->where('assignedIssues.data', fn ($issues) => collect($issues)->pluck('id')->all() === [$newer->id, $older->id])
                ->where('assignedIssues.data.0.board.name', $board->name)
                ->where('assignedIssues.data.0.status.name', 'In Progress')));
});

test('sprint summaries count tasks in each board\'s current sprint', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withSprints()->withStories()->withDefaultStatuses()->withMember($user)->create();
    $sprint = Sprint::factory()->for($board)->current()->create();
    $inSprint = fn (int $status, array $attributes = []) => Issue::factory()->inStatus($board->statuses[$status])->create(['sprint_id' => $sprint->id, ...$attributes]);
    $inSprint(0);
    $inSprint(1);
    $inSprint(2);
    $inSprint(0, ['role' => 'story']);
    Issue::factory()->inStatus($board->statuses[2])->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $deferred) => $deferred
            ->has('sprintSummaries', 1)
            ->where('sprintSummaries.0.board.data.id', $board->id)
            ->where('sprintSummaries.0.sprint.data.id', $sprint->id)
            ->where('sprintSummaries.0.total', 3)
            ->where('sprintSummaries.0.done', 1)));
});

test('sprint summaries skip boards without a current sprint and never create one', function () {
    $user = User::factory()->create();
    $withoutSprint = Board::factory()->withSprints()->withMember($user)->create();
    $ended = Board::factory()->withSprints()->withMember($user)->create();
    Sprint::factory()->for($ended)->between(today()->subDays(20), today()->subDays(10))->create();
    Board::factory()->withMember($user)->create();
    $sprintCount = Sprint::count();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $deferred) => $deferred->has('sprintSummaries', 0)));

    expect(Sprint::count())->toBe($sprintCount)->and($withoutSprint->sprints()->count())->toBe(0);
});

test('an empty current sprint reports zero tasks', function () {
    $user = User::factory()->create();
    $board = Board::factory()->withSprints()->withMember($user)->create();
    Sprint::factory()->for($board)->current()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $deferred) => $deferred
            ->where('sprintSummaries.0.total', 0)
            ->where('sprintSummaries.0.done', 0)));
});
