<?php

use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    $this->actingAs($this->user);
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->post(route('issues.watchers.store', $this->issue))->assertRedirect(route('login'));
    $this->delete(route('issues.watchers.destroy', $this->issue))->assertRedirect(route('login'));
});

test('members can watch and stop watching an issue', function () {
    $this->post(route('issues.watchers.store', $this->issue))->assertSessionHasNoErrors();
    $this->post(route('issues.watchers.store', $this->issue))->assertSessionHasNoErrors();
    expect($this->issue->watchers()->pluck('users.id')->all())->toBe([$this->user->id]);

    $this->delete(route('issues.watchers.destroy', $this->issue))->assertSessionHasNoErrors();
    expect($this->issue->watchers()->count())->toBe(0);
});

test('non-members cannot watch', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('issues.watchers.store', $this->issue))->assertForbidden();
    $this->delete(route('issues.watchers.destroy', $this->issue))->assertForbidden();
});

test('the issue page lists the watchers and says whether the viewer is one', function () {
    $other = User::factory()->create(['name' => 'Zed']);
    $this->board->users()->attach($other, ['role' => 'member']);
    $this->issue->watchers()->attach($other);

    $this->get(route('issues.show', $this->issue))->assertInertia(fn (Assert $page) => $page
        ->where('isWatching', false)
        ->has('watchers.data', 1)
        ->where('watchers.data.0.name', 'Zed'));

    $this->issue->watchers()->attach($this->user);

    $this->get(route('issues.show', $this->issue))->assertInertia(fn (Assert $page) => $page
        ->where('isWatching', true)
        ->has('watchers.data', 2));
});

describe('automatic watching', function () {
    test('authors watch the issues they create', function () {
        $this->post(route('boards.issues.store', $this->board), ['name' => 'Mine', 'role' => 'task']);

        expect(Issue::where('name', 'Mine')->sole()->watchers()->pluck('users.id')->all())->toBe([$this->user->id]);
    });

    test('assignees watch the issue, whether assigned on creation or later', function () {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->board->users()->attach([$first->id => ['role' => 'member'], $second->id => ['role' => 'member']]);

        $this->post(route('boards.issues.store', $this->board), ['name' => 'Assigned', 'role' => 'task', 'assigned_id' => $first->id]);
        $this->put(route('issues.update', $this->issue), ['assigned_id' => $second->id]);

        expect(Issue::where('name', 'Assigned')->sole()->watchers()->pluck('users.id')->sort()->values()->all())->toBe([$this->user->id, $first->id])
            ->and($this->issue->watchers()->pluck('users.id')->all())->toBe([$second->id]);
    });

    test('people who comment watch the issue', function () {
        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hello']);

        expect($this->issue->watchers()->pluck('users.id')->all())->toBe([$this->user->id]);
    });

    test('someone who stopped watching is not made to watch again by other people\'s changes', function () {
        $other = User::factory()->create();
        $this->board->users()->attach($other, ['role' => 'member']);
        $this->issue->watchers()->attach($this->user);
        $this->delete(route('issues.watchers.destroy', $this->issue));

        $this->actingAs($other)->put(route('issues.update', $this->issue), ['status_id' => $this->board->statuses[1]->id]);
        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi']);

        expect($this->issue->watchers()->pluck('users.id')->all())->toBe([$other->id]);
    });
});

test('leaving a board stops watching its issues', function () {
    $admin = User::factory()->create();
    $this->board->users()->attach($admin, ['role' => 'admin']);
    $elsewhere = Issue::factory()->for(Board::factory()->withDefaultStatuses()->withMember($this->user))->create();
    $this->issue->watchers()->attach($this->user);
    $elsewhere->watchers()->attach($this->user);

    $this->delete(route('boards.members.destroy', [$this->board, $this->user]))->assertSessionHasNoErrors();

    expect($this->issue->watchers()->count())->toBe(0)->and($elsewhere->watchers()->count())->toBe(1);
});
