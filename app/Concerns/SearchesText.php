<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait SearchesText
{
    /**
     * Match a column containing the search text, treating `%` and `_` in it
     * literally. `!` is the escape character because, unlike a backslash, it
     * behaves the same on SQLite, MySQL and PostgreSQL.
     *
     * @param  Builder<covariant Model>  $query
     * @param  literal-string  $column
     */
    protected static function whereColumnContains(Builder $query, string $column, string $search, string $boolean = 'and'): void
    {
        $term = '%'.strtr(trim($search), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';

        $query->whereRaw("{$column} like ? escape '!'", [$term], $boolean);
    }
}
