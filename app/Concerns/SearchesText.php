<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait SearchesText
{
    /**
     * Match a column containing the search text, ignoring case and treating
     * `%` and `_` in it literally. `!` is the escape character because, unlike
     * a backslash, it behaves the same on SQLite, MySQL and PostgreSQL. `like`
     * is already case-insensitive on SQLite and MySQL, so only PostgreSQL
     * needs `ilike`.
     *
     * @param  Builder<covariant Model>  $query
     * @param  literal-string  $column
     */
    protected static function whereColumnContains(Builder $query, string $column, string $search, string $boolean = 'and'): void
    {
        $term = '%'.strtr(trim($search), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';

        $query->whereRaw(
            $query->getModel()->getConnection()->getDriverName() === 'pgsql'
                ? "{$column} ilike ? escape '!'"
                : "{$column} like ? escape '!'",
            [$term],
            $boolean,
        );
    }
}
