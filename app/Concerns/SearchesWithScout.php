<?php

namespace App\Concerns;

use Laravel\Scout\Searchable;

trait SearchesWithScout
{
    use Searchable;

    /**
     * Whether Scout searches the application's own tables, which means the
     * searchable array may only hold real columns that should be matched as
     * text. Search services such as Meilisearch and Typesense also need the
     * attributes that results are filtered and sorted by.
     */
    public static function searchesInDatabase(): bool
    {
        return in_array(config('scout.driver'), ['database', 'collection', 'null'], true);
    }
}
