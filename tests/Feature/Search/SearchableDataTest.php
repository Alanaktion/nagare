<?php

use App\Models\Board;
use App\Models\Issue;

test('the database engine searches only text columns', function () {
    config(['scout.driver' => 'database']);
    $issue = Issue::factory()->for(Board::factory())->make(['name' => 'Fix', 'description' => 'Bug', 'label_names' => 'Urgent']);

    expect($issue->toSearchableArray())->toBe(['name' => 'Fix', 'description' => 'Bug', 'label_names' => 'Urgent'])
        ->and(Board::factory()->make(['name' => 'Roadmap'])->toSearchableArray())->toBe(['name' => 'Roadmap']);
});

test('search services also get the attributes results are filtered and sorted by', function (string $driver) {
    $board = Board::factory()->create();
    $issue = Issue::factory()->for($board)->create(['name' => 'Fix', 'description' => null, 'label_names' => null]);
    config(['scout.driver' => $driver]);

    expect($issue->toSearchableArray())->toMatchArray([
        'id' => (string) $issue->id,
        'board_id' => $board->id,
        'name' => 'Fix',
        'description' => null,
        'label_names' => null,
        'label_ids' => [],
        'assigned_id' => null,
        'is_closed' => false,
    ])->and($issue->toSearchableArray()['updated_at'])->toBeInt()
        ->and($board->toSearchableArray())->toMatchArray(['id' => (string) $board->id, 'name' => $board->name]);
})->with(['meilisearch', 'typesense']);

test('every engine used with the app has index settings for the attributes it filters on', function () {
    expect(config('scout.meilisearch.index-settings.'.Issue::class.'.filterableAttributes'))->toBe(['board_id', 'assigned_id', 'label_ids', 'is_closed'])
        ->and(config('scout.meilisearch.index-settings.'.Board::class.'.filterableAttributes'))->toBe(['id'])
        ->and(collect(config('scout.typesense.model-settings.'.Issue::class.'.collection-schema.fields'))->pluck('name')->all())
        ->toContain('id', 'board_id', 'name', 'description', 'label_names', 'label_ids', 'assigned_id', 'is_closed', 'updated_at');
});
