<?php

use App\Enums\LabelColor;
use App\Events\BoardUpdated;
use App\Http\Requests\Labels\StoreLabelRequest;
use App\Models\Board;
use App\Models\Issue;
use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    $this->actingAs($this->user);
});

test('guests are redirected to login', function () {
    $label = Label::factory()->for($this->board)->create();
    auth()->logout();

    $this->post(route('boards.labels.store', $this->board), ['name' => 'Bug', 'color' => 'red'])->assertRedirect(route('login'));
    $this->put(route('labels.update', $label), ['name' => 'Bug', 'color' => 'red'])->assertRedirect(route('login'));
    $this->delete(route('labels.destroy', $label))->assertRedirect(route('login'));
});

test('members can add, rename, recolour and delete labels', function () {
    Event::fake([BoardUpdated::class]);

    $this->post(route('boards.labels.store', $this->board), ['name' => 'Bug', 'color' => 'red'])->assertSessionHasNoErrors();
    $label = $this->board->labels()->sole();
    expect($label)->name->toBe('Bug')->color->toBe(LabelColor::Red);

    $this->put(route('labels.update', $label), ['name' => 'Defect', 'color' => 'orange'])->assertSessionHasNoErrors();
    expect($label->fresh())->name->toBe('Defect')->color->toBe(LabelColor::Orange);

    $this->delete(route('labels.destroy', $label))->assertSessionHasNoErrors();
    expect($this->board->labels()->count())->toBe(0);

    Event::assertDispatchedTimes(BoardUpdated::class, 3);
});

test('non-members cannot manage labels', function () {
    $label = Label::factory()->for($this->board)->create();
    $this->actingAs(User::factory()->create());

    $this->post(route('boards.labels.store', $this->board), ['name' => 'Bug', 'color' => 'red'])->assertForbidden();
    $this->put(route('labels.update', $label), ['name' => 'Bug', 'color' => 'red'])->assertForbidden();
    $this->delete(route('labels.destroy', $label))->assertForbidden();
});

test('label input is validated', function (array $input, string $errorKey) {
    Label::factory()->for($this->board)->create(['name' => 'Taken']);

    $this->post(route('boards.labels.store', $this->board), $input)->assertSessionHasErrors($errorKey);
})->with([
    'missing name' => [['name' => '', 'color' => 'red'], 'name'],
    'long name' => [['name' => str_repeat('a', 51), 'color' => 'red'], 'name'],
    'duplicate name' => [['name' => 'Taken', 'color' => 'red'], 'name'],
    'unknown colour' => [['name' => 'New', 'color' => 'chartreuse'], 'color'],
    'missing colour' => [['name' => 'New'], 'color'],
]);

test('names only need to be unique within a board and may be kept on update', function () {
    $label = Label::factory()->for($this->board)->create(['name' => 'Bug']);
    Label::factory()->create(['name' => 'Feature']);

    $this->post(route('boards.labels.store', Board::factory()->withMember($this->user)->create()), ['name' => 'Bug', 'color' => 'red'])
        ->assertSessionHasNoErrors();
    $this->put(route('labels.update', $label), ['name' => 'Bug', 'color' => 'blue'])->assertSessionHasNoErrors();
});

test('a board has a limited number of labels', function () {
    Label::factory()->for($this->board)->count(StoreLabelRequest::MAXIMUM_PER_BOARD)->sequence(fn ($sequence) => ['name' => "Label {$sequence->index}"])->create();

    $this->post(route('boards.labels.store', $this->board), ['name' => 'One too many', 'color' => 'red'])->assertSessionHasErrors('name');
});

test('the settings page lists labels with their issue counts', function () {
    $label = Label::factory()->for($this->board)->create(['name' => 'Bug']);
    Issue::factory()->inStatus($this->board->statuses[0])->create()->labels()->attach($label);

    $this->get(route('boards.edit', $this->board))
        ->assertInertia(fn (Assert $page) => $page
            ->has('labels.data', 1)
            ->where('labels.data.0.name', 'Bug')
            ->where('labels.data.0.issues_count', 1));
});

describe('on issues', function () {
    beforeEach(function () {
        $this->bug = Label::factory()->for($this->board)->create(['name' => 'Bug']);
        $this->design = Label::factory()->for($this->board)->create(['name' => 'Design']);
        $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    });

    test('issues are created with labels', function () {
        $this->post(route('boards.issues.store', $this->board), ['name' => 'Crash', 'role' => 'task', 'label_ids' => [$this->bug->id, $this->design->id]])
            ->assertSessionHasNoErrors();

        expect(Issue::where('name', 'Crash')->sole()->labels->pluck('name')->sort()->values()->all())->toBe(['Bug', 'Design']);
    });

    test('labels are replaced, cleared, or left alone on update', function () {
        $this->put(route('issues.update', $this->issue), ['label_ids' => [$this->bug->id]])->assertSessionHasNoErrors();
        expect($this->issue->labels()->pluck('name')->all())->toBe(['Bug']);

        $this->put(route('issues.update', $this->issue), ['name' => 'Renamed'])->assertSessionHasNoErrors();
        expect($this->issue->labels()->count())->toBe(1);

        $this->put(route('issues.update', $this->issue), ['label_ids' => [$this->design->id]])->assertSessionHasNoErrors();
        expect($this->issue->labels()->pluck('name')->all())->toBe(['Design']);

        $this->put(route('issues.update', $this->issue), ['label_ids' => []])->assertSessionHasNoErrors();
        expect($this->issue->labels()->count())->toBe(0);
    });

    test('labels must belong to the issue\'s board', function () {
        $foreign = Label::factory()->create();

        $this->put(route('issues.update', $this->issue), ['label_ids' => [$foreign->id]])->assertSessionHasErrors('label_ids.0');
        $this->put(route('issues.update', $this->issue), ['label_ids' => [$this->bug->id, $this->bug->id]])->assertSessionHasErrors('label_ids.0');
        $this->post(route('boards.issues.store', $this->board), ['name' => 'x', 'role' => 'task', 'label_ids' => [$foreign->id]])->assertSessionHasErrors('label_ids.0');
    });

    test('deleting a label removes it from its issues', function () {
        $this->issue->labels()->attach($this->bug);

        $this->delete(route('labels.destroy', $this->bug))->assertSessionHasNoErrors();

        expect($this->issue->labels()->count())->toBe(0)
            ->and(Issue::find($this->issue->id))->not->toBeNull();
    });

    test('the board and issue pages include labels', function () {
        $this->issue->labels()->attach($this->bug);

        $this->get(route('boards.show', $this->board))
            ->assertInertia(fn (Assert $page) => $page
                ->has('labels.data', 2)
                ->where('issues.data.0.labels.0.name', 'Bug'));

        $this->get(route('issues.show', $this->issue))
            ->assertInertia(fn (Assert $page) => $page
                ->has('labels.data', 2)
                ->where('issue.data.labels.0.name', 'Bug'));
    });
});

describe('searchable label names', function () {
    beforeEach(function () {
        $this->bug = Label::factory()->for($this->board)->create(['name' => 'Bug']);
        $this->design = Label::factory()->for($this->board)->create(['name' => 'Design']);
        $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    });

    test('an issue keeps its label names for searching as its labels change', function () {
        $this->put(route('issues.update', $this->issue), ['label_ids' => [$this->design->id, $this->bug->id]]);
        expect($this->issue->fresh()->label_names)->toBe('Bug Design');

        $this->put(route('issues.update', $this->issue), ['label_ids' => [$this->bug->id]]);
        expect($this->issue->fresh()->label_names)->toBe('Bug');

        $this->put(route('issues.update', $this->issue), ['name' => 'Renamed']);
        expect($this->issue->fresh()->label_names)->toBe('Bug');

        $this->put(route('issues.update', $this->issue), ['label_ids' => []]);
        expect($this->issue->fresh()->label_names)->toBeNull();
    });

    test('new issues start with their label names', function () {
        $this->post(route('boards.issues.store', $this->board), ['name' => 'Crash', 'role' => 'task', 'label_ids' => [$this->bug->id]]);

        expect(Issue::where('name', 'Crash')->sole()->label_names)->toBe('Bug');
    });

    test('renaming or deleting a label updates its issues', function () {
        $this->put(route('issues.update', $this->issue), ['label_ids' => [$this->bug->id, $this->design->id]]);

        $this->put(route('labels.update', $this->bug), ['name' => 'Defect', 'color' => 'red']);
        expect($this->issue->fresh()->label_names)->toBe('Defect Design');

        $this->delete(route('labels.destroy', $this->design));
        expect($this->issue->fresh()->label_names)->toBe('Defect');

        $this->put(route('labels.update', $this->bug), ['name' => 'Defect', 'color' => 'blue']);
        expect($this->issue->fresh()->label_names)->toBe('Defect');
    });

    test('issues are found by a label added through the app', function () {
        $this->put(route('issues.update', $this->issue), ['label_ids' => [$this->design->id]]);

        $this->get(route('search', ['q' => 'design']))
            ->assertInertia(fn (Assert $page) => $page->where('issues.data.0.id', $this->issue->id));
    });
});
