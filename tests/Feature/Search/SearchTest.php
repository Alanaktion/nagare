<?php

use App\Models\Board;
use App\Models\Issue;
use App\Models\Label;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create(['name' => 'Roadmap']);
    $this->actingAs($this->user);
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get(route('search', ['q' => 'anything']))->assertRedirect(route('login'));
});

test('issues are found by name, description and label', function () {
    $todo = $this->board->statuses[0];
    Issue::factory()->inStatus($todo)->create(['name' => 'Fix login crash', 'description' => null]);
    Issue::factory()->inStatus($todo)->create(['name' => 'Polish', 'description' => 'The login page needs love']);
    Issue::factory()->inStatus($todo)->create(['name' => 'Unrelated', 'description' => null]);
    $tagged = Issue::factory()->inStatus($todo)->create(['name' => 'Tagged', 'description' => null]);
    $tagged->labels()->attach(Label::factory()->for($this->board)->create(['name' => 'login']));
    $tagged->refreshLabelNames();

    $this->get(route('search', ['q' => 'login']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('search/Index')
            ->where('query', 'login')
            ->where('issues.data', fn ($issues) => collect($issues)->pluck('name')->sort()->values()->all() === ['Fix login crash', 'Polish', 'Tagged']));
});

test('boards are found by name', function () {
    Board::factory()->withMember($this->user)->create(['name' => 'Other']);

    $this->get(route('search', ['q' => 'road']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('boards.data', 1)
            ->where('boards.data.0.name', 'Roadmap'));
});

test('only issues and boards the user belongs to are searched', function () {
    $secret = Board::factory()->withDefaultStatuses()->create(['name' => 'Secret roadmap']);
    Issue::factory()->inStatus($secret->statuses[0])->create(['name' => 'Secret login work']);
    $deleted = Board::factory()->withDefaultStatuses()->withMember($this->user)->create(['name' => 'Old roadmap']);
    Issue::factory()->inStatus($deleted->statuses[0])->create(['name' => 'Old login work']);
    $deleted->delete();
    Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => 'Mine login work']);

    $this->get(route('search', ['q' => 'login']))
        ->assertInertia(fn (Assert $page) => $page->where('issues.data', fn ($issues) => collect($issues)->pluck('name')->all() === ['Mine login work']));
    $this->get(route('search', ['q' => 'roadmap']))
        ->assertInertia(fn (Assert $page) => $page->has('boards.data', 1)->where('boards.data.0.name', 'Roadmap'));
});

test('short or empty searches return nothing', function () {
    Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => 'abc']);

    foreach (['', 'a', '  '] as $query) {
        $this->get(route('search', ['q' => $query]))
            ->assertInertia(fn (Assert $page) => $page->has('issues.data', 0)->has('boards.data', 0));
    }
});

test('results are paginated', function () {
    Issue::factory()->inStatus($this->board->statuses[0])->count(25)->create(['name' => 'Match']);

    $this->get(route('search', ['q' => 'match']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 20)
            ->where('issues.meta.total', 25)
            ->where('issues.meta.last_page', 2));

    $this->get(route('search', ['q' => 'match', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('issues.data', 5));
});

test('results carry what the search page shows', function () {
    $issue = Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => 'Findable']);
    $issue->labels()->attach(Label::factory()->for($this->board)->create(['name' => 'Bug']));

    $this->get(route('search', ['q' => 'findable']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('issues.data.0.board.name', 'Roadmap')
            ->where('issues.data.0.status.name', 'To Do')
            ->where('issues.data.0.labels.0.name', 'Bug'));
});
