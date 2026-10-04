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
    Issue::factory()->inStatus($todo)->create(['name' => 'Tagged', 'description' => null])
        ->labels()->attach(Label::factory()->for($this->board)->create(['name' => 'login']));

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

test('wildcard characters in the search are matched literally', function () {
    Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => '100% done', 'description' => null]);
    Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => '100 items', 'description' => null]);

    $this->get(route('search', ['q' => '100%']))
        ->assertInertia(fn (Assert $page) => $page->where('issues.data', fn ($issues) => collect($issues)->pluck('name')->all() === ['100% done']));
});

test('short or empty searches return nothing', function () {
    Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => 'abc']);

    foreach (['', 'a', '  '] as $query) {
        $this->get(route('search', ['q' => $query]))
            ->assertInertia(fn (Assert $page) => $page->has('issues.data', 0)->has('boards.data', 0));
    }
});

test('results are paginated and open issues come first', function () {
    $todo = $this->board->statuses[0];
    Issue::factory()->inStatus($todo)->count(25)->create(['name' => 'Match open']);
    Issue::factory()->inStatus($this->board->statuses[2])->create(['name' => 'Match closed']);

    $this->get(route('search', ['q' => 'match']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 20)
            ->where('issues.meta.total', 26)
            ->where('issues.data.0.name', 'Match open'));
});
