<?php

use App\Models\Board;
use App\Models\Issue;
use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;

/*
 * These tests run against the database engine, and also against a real
 * Typesense server when TYPESENSE_TEST_HOST is set (for example to
 * `127.0.0.1` with TYPESENSE_TEST_PORT and TYPESENSE_TEST_API_KEY), so both
 * engines are held to the same behaviour.
 */

/**
 * @return array<string, array{string}>
 */
function searchEngines(): array
{
    return [
        'database' => ['database'],
        'typesense' => ['typesense'],
    ];
}

/**
 * Switch Scout to an engine, using unique collection names for Typesense so
 * runs don't see each other's data.
 */
function useSearchEngine(string $engine): void
{
    if ($engine === 'typesense') {
        if (! env('TYPESENSE_TEST_HOST')) {
            test()->markTestSkipped('Set TYPESENSE_TEST_HOST to run the search tests against Typesense.');
        }

        config([
            'scout.driver' => 'typesense',
            'scout.prefix' => 'test_'.Str::lower(Str::random(8)).'_',
            'scout.queue' => false,
            'scout.typesense.client-settings.api_key' => env('TYPESENSE_TEST_API_KEY', 'testkey'),
            'scout.typesense.client-settings.nodes' => [[
                'host' => env('TYPESENSE_TEST_HOST'),
                'port' => env('TYPESENSE_TEST_PORT', '8108'),
                'path' => '',
                'protocol' => 'http',
            ]],
            'scout.typesense.client-settings.nearest_node' => [
                'host' => env('TYPESENSE_TEST_HOST'),
                'port' => env('TYPESENSE_TEST_PORT', '8108'),
                'path' => '',
                'protocol' => 'http',
            ],
        ]);
        app(EngineManager::class)->forgetEngines();
    }
}

/**
 * @return list<string>
 */
function foundIssueNames(Assert $page): array
{
    return collect($page->toArray()['props']['issues']['data'])->pluck('name')->sort()->values()->all();
}

/**
 * The names of the issues a search finds, in alphabetical order.
 *
 * @param  array<string, mixed>  $query
 * @return list<string>
 */
function searchFor(array $query): array
{
    $names = [];

    test()->get(route('search', $query))->assertOk()->assertInertia(function (Assert $page) use (&$names): void {
        $names = foundIssueNames($page);
    });

    return $names;
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    if (config('scout.driver') === 'typesense') {
        foreach ([new Issue, new Board] as $model) {
            try {
                app(EngineManager::class)->engine()->deleteIndex($model->searchableAs());
            } catch (Throwable) {
                // Nothing to clean up if the collection was never created.
            }
        }
    }
});

/**
 * Two boards the user is on and one they aren't, each with "Login" issues.
 *
 * @return array<string, mixed>
 */
function seedLoginIssues(User $user): array
{
    $boardA = Board::factory()->withDefaultStatuses()->withMember($user)->create(['name' => 'Alpha']);
    $boardB = Board::factory()->withDefaultStatuses()->withMember($user)->create(['name' => 'Beta']);
    $boardC = Board::factory()->withDefaultStatuses()->create(['name' => 'Gamma']);
    $bug = Label::factory()->for($boardA)->create(['name' => 'Bug']);
    $design = Label::factory()->for($boardA)->create(['name' => 'Design']);
    [$todo, , $done] = $boardA->statuses;

    $crash = Issue::factory()->inStatus($todo)->create(['name' => 'Login crash', 'description' => null, 'assigned_id' => $user->id]);
    $polish = Issue::factory()->inStatus($done)->create(['name' => 'Login polish', 'description' => null]);
    $docs = Issue::factory()->inStatus($todo)->create(['name' => 'Login docs', 'description' => null]);
    Issue::factory()->inStatus($boardB->statuses[0])->create(['name' => 'Login timeout', 'description' => null]);
    Issue::factory()->inStatus($boardC->statuses[0])->create(['name' => 'Login secret', 'description' => null]);

    $crash->labels()->attach($bug);
    $crash->refreshLabelNames();
    $polish->labels()->attach($design);
    $polish->refreshLabelNames();
    Issue::reindex([$crash->id, $polish->id]);

    return compact('boardA', 'boardB', 'boardC', 'bug', 'design', 'crash', 'polish', 'docs');
}

test('search can be narrowed by board, state, assignee and label', function (string $engine) {
    useSearchEngine($engine);
    $data = seedLoginIssues($this->user);
    $all = ['Login crash', 'Login docs', 'Login polish', 'Login timeout'];

    $found = fn (array $query) => $this->get(route('search', ['q' => 'login', ...$query]));

    $found([])->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe($all));
    $found(['board' => $data['boardA']->id])->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login crash', 'Login docs', 'Login polish']));
    $found(['state' => 'closed'])->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login polish']));
    $found(['state' => 'open'])->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login crash', 'Login docs', 'Login timeout']));
    $found(['mine' => 1])->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login crash']));
    $found(['board' => $data['boardA']->id, 'label' => $data['design']->id])
        ->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login polish']));
    $found(['board' => $data['boardA']->id, 'label' => $data['bug']->id, 'state' => 'open', 'mine' => 1])
        ->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login crash']));
    $found(['board' => $data['boardA']->id, 'label' => $data['bug']->id, 'state' => 'closed'])
        ->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe([]));
})->with(searchEngines());

test('filters that do not apply are ignored', function (string $engine) {
    useSearchEngine($engine);
    $data = seedLoginIssues($this->user);

    $this->get(route('search', ['q' => 'login', 'board' => $data['boardC']->id]))
        ->assertInertia(fn (Assert $page) => $page->where('filters.board', null)->has('issues.data', 4));
    $this->get(route('search', ['q' => 'login', 'label' => $data['bug']->id]))
        ->assertInertia(fn (Assert $page) => $page->where('filters.label', null)->has('issues.data', 4));
    $this->get(route('search', ['q' => 'login', 'board' => $data['boardB']->id, 'label' => $data['bug']->id]))
        ->assertInertia(fn (Assert $page) => $page->where('filters.label', null)->has('issues.data', 1));
    $this->get(route('search', ['q' => 'login', 'state' => 'bogus']))
        ->assertInertia(fn (Assert $page) => $page->where('filters.state', null)->has('issues.data', 4));
})->with(searchEngines());

test('the search page offers the user\'s boards and the selected board\'s labels', function () {
    $data = seedLoginIssues($this->user);

    $this->get(route('search', ['q' => 'login', 'board' => $data['boardA']->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('boardOptions', fn ($options) => collect($options)->pluck('name')->all() === ['Alpha', 'Beta'])
            ->where('labelOptions.data', fn ($labels) => collect($labels)->pluck('name')->all() === ['Bug', 'Design'])
            ->where('filters.board', $data['boardA']->id));

    $this->get(route('search', ['q' => 'login']))
        ->assertInertia(fn (Assert $page) => $page->has('labelOptions.data', 0));
});

test('search results follow changes to issues, labels, assignees and statuses', function (string $engine) {
    useSearchEngine($engine);
    $data = seedLoginIssues($this->user);
    $board = $data['boardA'];
    $filtered = fn (array $query) => searchFor(['q' => 'login', 'board' => $board->id, ...$query]);

    // Labels
    $this->put(route('issues.update', $data['docs']), ['label_ids' => [$data['bug']->id]]);
    expect($filtered(['label' => $data['bug']->id]))->toBe(['Login crash', 'Login docs']);
    $this->put(route('labels.update', $data['bug']), ['name' => 'Defect', 'color' => 'red']);
    expect(searchFor(['q' => 'defect']))->toBe(['Login crash', 'Login docs']);
    $this->delete(route('labels.destroy', $data['bug']));
    expect(searchFor(['q' => 'defect']))->toBe([]);

    // Closing through a move
    $this->put(route('issues.update', $data['docs']), ['status_id' => $board->statuses[2]->id]);
    expect($filtered(['state' => 'closed']))->toBe(['Login docs', 'Login polish']);

    // Assignment
    $this->put(route('issues.update', $data['docs']), ['assigned_id' => $this->user->id]);
    expect($filtered(['mine' => 1]))->toBe(['Login crash', 'Login docs']);

    // Deleting
    $this->delete(route('issues.destroy', $data['docs']));
    expect($filtered([]))->toBe(['Login crash', 'Login polish']);
})->with(searchEngines());

test('removing a member unassigns their issues from search filters', function (string $engine) {
    useSearchEngine($engine);
    $data = seedLoginIssues($this->user);
    $board = $data['boardA'];
    $other = User::factory()->create();
    $board->users()->attach($other, ['role' => 'admin']);
    $this->put(route('issues.update', $data['docs']), ['assigned_id' => $other->id]);

    $this->actingAs($this->user)->delete(route('boards.members.destroy', [$board, $this->user]))->assertSessionHasNoErrors();
    $this->actingAs($other);

    $this->get(route('search', ['q' => 'login', 'mine' => 1]))
        ->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login docs']));
})->with(searchEngines());

test('closing statuses and status removal keep the closed filter in step', function (string $engine) {
    useSearchEngine($engine);
    $data = seedLoginIssues($this->user);
    $board = $data['boardA'];
    [$todo, $progress, $done] = $board->statuses;

    // Toggle "In Progress" to a closing status and move "Login docs" into it with a status removal.
    $this->put(route('issues.update', $data['docs']), ['status_id' => $progress->id]);
    $statuses = $board->statuses->map(fn ($status) => ['id' => $status->id, 'name' => $status->name, 'is_closed' => $status->id === $progress->id || $status->is_closed])->all();
    $this->put(route('boards.update', $board), ['name' => $board->name, 'has_stories' => false, 'has_sprints' => false, 'statuses' => $statuses])->assertSessionHasNoErrors();

    $this->get(route('search', ['q' => 'login', 'state' => 'closed']))
        ->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login docs', 'Login polish']));

    $withoutProgress = collect($statuses)->reject(fn ($status) => $status['id'] === $todo->id)->values()->all();
    $this->put(route('boards.update', $board), [
        'name' => $board->name, 'has_stories' => false, 'has_sprints' => false,
        'statuses' => $withoutProgress, 'status_moves' => [$todo->id => $done->id],
    ])->assertSessionHasNoErrors();

    $this->get(route('search', ['q' => 'login', 'state' => 'closed']))
        ->assertInertia(fn (Assert $page) => expect(foundIssueNames($page))->toBe(['Login crash', 'Login docs', 'Login polish']));
})->with(searchEngines());

test('boards are searchable and limited to the user\'s boards', function (string $engine) {
    useSearchEngine($engine);
    seedLoginIssues($this->user);

    $this->get(route('search', ['q' => 'alp']))
        ->assertInertia(fn (Assert $page) => $page->has('boards.data', 1)->where('boards.data.0.name', 'Alpha'));
    $this->get(route('search', ['q' => 'gamma']))
        ->assertInertia(fn (Assert $page) => $page->has('boards.data', 0));
})->with(searchEngines());
