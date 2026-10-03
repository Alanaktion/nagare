<?php

namespace App\Actions\Boards;

use App\Models\Board;

class SyncBoardStatuses
{
    /**
     * Make the board's statuses match the submitted ordered list.
     *
     * Entries with an `id` update that status, entries without one are
     * created, and existing statuses missing from the list are deleted.
     * Each entry's position in the list becomes its sort value.
     *
     * @param  array<int, array{id?: int|null, name: string, is_closed?: bool}>  $statuses
     */
    public function handle(Board $board, array $statuses): void
    {
        $keptIds = collect($statuses)->pluck('id')->filter()->all();

        $board->statuses()->whereNotIn('id', $keptIds)->delete();

        foreach (array_values($statuses) as $position => $data) {
            $attributes = [
                'name' => $data['name'],
                'sort' => $position,
                'is_closed' => $data['is_closed'] ?? false,
            ];

            if (! empty($data['id'])) {
                $board->statuses()->whereKey($data['id'])->update($attributes);
            } else {
                $board->statuses()->create($attributes);
            }
        }
    }
}
