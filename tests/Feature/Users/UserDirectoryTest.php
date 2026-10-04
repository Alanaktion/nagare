<?php

use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->viewer = User::factory()->create(['name' => 'Vera Viewer']);
});

test('guests are redirected to login', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->get(route('users.show', $this->viewer))->assertRedirect(route('login'));
});

test('the directory lists every user alphabetically', function () {
    User::factory()->create(['name' => 'Zoe Zed']);
    User::factory()->create(['name' => 'Aaron Able']);

    $this->actingAs($this->viewer)->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/Index')
            ->has('users.data', 3)
            ->where('users.data.0.name', 'Aaron Able')
            ->where('users.data.2.name', 'Zoe Zed'));
});

test('the directory can be searched by name or email', function () {
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.test']);
    User::factory()->create(['name' => 'Alan Turing', 'email' => 'alan@bletchley.test']);

    $this->actingAs($this->viewer);
    $this->get(route('users.index', ['search' => 'hopper']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.name', 'Grace Hopper')->where('search', 'hopper'));
    $this->get(route('users.index', ['search' => 'bletchley']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.name', 'Alan Turing'));
    $this->get(route('users.index', ['search' => '100%']))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
});

test('the directory is paginated and keeps the search', function () {
    User::factory()->count(30)->create(['name' => 'Pat Person']);

    $this->actingAs($this->viewer)->get(route('users.index', ['search' => 'Pat']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 24)
            ->where('users.meta.total', 30)
            ->where('users.links.next', fn ($url) => str_contains($url, 'search=Pat')));
});

test('a profile shows only the boards you share with that user', function () {
    $colleague = User::factory()->create();
    $shared = Board::factory()->withMember($this->viewer)->withMember($colleague)->create();
    Board::factory()->withMember($colleague)->create();
    Board::factory()->withMember($this->viewer)->create();

    $this->actingAs($this->viewer)->get(route('users.show', $colleague))
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/Show')
            ->where('profile.data.id', $colleague->id)
            ->has('boards.data', 1)
            ->where('boards.data.0.id', $shared->id));
});

test('your own profile shows all of your boards', function () {
    Board::factory()->count(2)->withMember($this->viewer)->create();

    $this->actingAs($this->viewer)->get(route('users.show', $this->viewer))
        ->assertInertia(fn (Assert $page) => $page->has('boards.data', 2));
});

test('assigned issues load separately and only include shared boards, open ones first', function () {
    $colleague = User::factory()->create();
    $shared = Board::factory()->withDefaultStatuses()->withMember($this->viewer)->withMember($colleague)->create();
    $private = Board::factory()->withDefaultStatuses()->withMember($colleague)->create();
    $closed = Issue::factory()->inStatus($shared->statuses[2])->create(['assigned_id' => $colleague->id, 'name' => 'Closed one']);
    $open = Issue::factory()->inStatus($shared->statuses[0])->create(['assigned_id' => $colleague->id, 'name' => 'Open one']);
    Issue::factory()->inStatus($private->statuses[0])->create(['assigned_id' => $colleague->id]);
    Issue::factory()->inStatus($shared->statuses[0])->create(['assigned_id' => $this->viewer->id]);

    $this->actingAs($this->viewer)->get(route('users.show', $colleague))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('assignedIssues')
            ->loadDeferredProps(fn (Assert $deferred) => $deferred
                ->has('assignedIssues.data', 2)
                ->where('assignedIssues.data.0.id', $open->id)
                ->where('assignedIssues.data.1.id', $closed->id)
                ->where('assignedIssues.data.0.board.name', $shared->name)
                ->where('assignedIssues.data.0.status.name', 'To Do')));
});
