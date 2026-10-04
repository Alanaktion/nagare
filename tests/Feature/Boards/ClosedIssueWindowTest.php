<?php

use App\Enums\SprintCycle;
use App\Models\Board;
use App\Models\Issue;
use App\Models\Sprint;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/**
 * @return array{open: Issue, recent: Issue, old: Issue}
 */
function closedIssueFixtures(Board $board, ?Sprint $sprint = null): array
{
    [$todo, , $done] = $board->statuses;
    $attributes = ['sprint_id' => $sprint?->id];

    return [
        'open' => Issue::factory()->inStatus($todo)->create($attributes),
        'recent' => Issue::factory()->inStatus($done)->create([...$attributes, 'closed_at' => now()->subDays(3)]),
        'old' => Issue::factory()->inStatus($done)->create([...$attributes, 'closed_at' => now()->subDays(30)]),
    ];
}

test('kanban boards leave out issues closed more than two weeks ago', function () {
    $board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    $issues = closedIssueFixtures($board);

    $this->get(route('boards.show', $board))
        ->assertInertia(fn (Assert $page) => $page
            ->where('issues.data', fn ($data) => collect($data)->pluck('id')->sort()->values()->all()
                === [$issues['open']->id, $issues['recent']->id])
            ->where('olderClosedCount', 1)
            ->where('withOlderClosed', false));
});

test('older closed issues can be shown on request', function () {
    $board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    closedIssueFixtures($board);

    $this->get(route('boards.show', [$board, 'closed' => 'all']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 3)
            ->where('olderClosedCount', 1)
            ->where('withOlderClosed', true));
});

test('old closed stories stay so their tasks have a lane', function () {
    $board = Board::factory()->withStories()->withDefaultStatuses()->withMember($this->user)->create();
    $story = Issue::factory()->story()->inStatus($board->statuses[2])->create(['closed_at' => now()->subDays(30)]);
    Issue::factory()->inStatus($board->statuses[0])->create(['parent_id' => $story->id]);

    $this->get(route('boards.show', $board))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 2)
            ->where('olderClosedCount', 0));
});

test('the backlog is windowed but sprints show every closed issue', function () {
    $this->travelTo('2026-10-07 12:00:00');
    $board = Board::factory()->withSprints(SprintCycle::Weekly)->withDefaultStatuses()->withMember($this->user)->create();
    $sprint = Sprint::factory()->for($board)->current()->create();
    closedIssueFixtures($board);
    closedIssueFixtures($board, $sprint);

    $this->get(route('boards.backlog', $board))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 2)
            ->where('olderClosedCount', 1));

    $this->get(route('boards.backlog', [$board, 'closed' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->has('issues.data', 3));

    $this->get(route('boards.sprints.show', [$board, $sprint]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 3)
            ->where('olderClosedCount', 0));
});
