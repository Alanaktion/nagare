<?php

use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create(['has_stories' => true]);
    [$this->todo, $this->progress, $this->done] = $this->board->statuses;
    $this->epic = Issue::factory()->epic()->inStatus($this->todo)->create(['name' => 'Launch']);
});

test('epics can be created only on boards with stories', function () {
    $this->actingAs($this->user)
        ->post(route('boards.issues.store', $this->board), ['name' => 'Another', 'role' => 'epic'])
        ->assertSessionHasNoErrors();

    $this->board->update(['has_stories' => false]);

    $this->post(route('boards.issues.store', $this->board), ['name' => 'Nope', 'role' => 'epic'])
        ->assertSessionHasErrors('role');
});

test('a story can be placed in an epic of its own board only', function () {
    $story = Issue::factory()->story()->inStatus($this->todo)->create();
    $task = Issue::factory()->inStatus($this->todo)->create();
    $foreignEpic = Issue::factory()->epic()->create();
    $this->actingAs($this->user);

    $this->put(route('issues.update', $story), ['parent_id' => $this->epic->id])->assertSessionHasNoErrors();
    expect($story->fresh()->parent_id)->toBe($this->epic->id);

    $this->put(route('issues.update', $story), ['parent_id' => $foreignEpic->id])->assertSessionHasErrors('parent_id');
    $this->put(route('issues.update', $task), ['parent_id' => $this->epic->id])->assertSessionHasErrors('parent_id');
});

test('epics have no parent and no sprint', function () {
    $story = Issue::factory()->story()->inStatus($this->todo)->create();
    $this->board->update(['has_sprints' => true]);

    $this->actingAs($this->user)
        ->put(route('issues.update', $this->epic), ['parent_id' => $story->id])
        ->assertSessionHasErrors('parent_id');
    $this->put(route('issues.update', $this->epic), ['sprint_id' => 1])->assertSessionHasErrors('sprint_id');
});

test('epics are left off the board and offered to stories', function () {
    $this->actingAs($this->user)->get(route('boards.show', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->has('issues.data', 0)
            ->has('epics.data', 1)
            ->where('epics.data.0.name', 'Launch'));
});

test('the epics view rolls tasks up through stories', function () {
    $story = Issue::factory()->story()->inStatus($this->todo)->create(['parent_id' => $this->epic->id]);
    Issue::factory()->inStatus($this->todo)->create(['parent_id' => $story->id]);
    Issue::factory()->inStatus($this->done)->create(['parent_id' => $story->id, 'closed_at' => now()]);
    Issue::factory()->inStatus($this->done)->create(['closed_at' => now()]);

    $this->actingAs($this->user)->get(route('boards.epics.index', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->component('epics/Index')
            ->where('epics.data.0.tasks_done', 1)
            ->where('epics.data.0.tasks_total', 2)
            ->where('epics.data.0.stories.0.tasks_total', 2));
});

test('the epics view needs a board with stories and membership', function () {
    $this->actingAs(User::factory()->create())->get(route('boards.epics.index', $this->board))->assertForbidden();

    $this->board->update(['has_stories' => false]);

    $this->actingAs($this->user)->get(route('boards.epics.index', $this->board))->assertNotFound();
});

test('deleting an epic detaches its stories', function () {
    $story = Issue::factory()->story()->inStatus($this->todo)->create(['parent_id' => $this->epic->id]);

    $this->actingAs($this->user)->delete(route('issues.destroy', $this->epic))->assertRedirect();

    expect($story->fresh()->parent_id)->toBeNull();
});
