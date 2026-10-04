<?php

namespace App\Actions\Boards;

use App\Models\Board;
use App\Models\Status;

class SyncBoardStatuses
{
    /**
     * Make the board's statuses match the submitted ordered list.
     *
     * Entries with an `id` update that status, entries without one are
     * created, and existing statuses missing from the list are deleted.
     * Each entry's position in the list becomes its sort value. Issues in a
     * deleted status are moved to the status given in `$moves`, keyed by the
     * deleted status id. Toggling a status's closing flag opens or closes
     * its existing issues.
     *
     * @param  array<int, array{id?: int|null, name: string, is_closed?: bool}>  $statuses
     * @param  array<int|string, int|string>  $moves
     */
    public function handle(Board $board, array $statuses, array $moves = []): void
    {
        $keptIds = collect($statuses)->pluck('id')->filter()->all();
        $removed = $board->statuses()->whereNotIn('id', $keptIds)->get();
        $existing = $board->statuses()->whereIn('id', $keptIds)->get()->keyBy('id');

        foreach (array_values($statuses) as $position => $data) {
            $attributes = [
                'name' => $data['name'],
                'sort' => $position,
                'is_closed' => $data['is_closed'] ?? false,
            ];

            if (empty($data['id'])) {
                $board->statuses()->create($attributes);

                continue;
            }

            $status = $existing[$data['id']]->fill($attributes);
            $status->save();

            if ($status->wasChanged('is_closed')) {
                $this->syncIssueClosedTimestamps($status);
            }
        }

        foreach ($removed as $status) {
            $target = isset($moves[$status->id]) ? $existing->get((int) $moves[$status->id]) : null;

            if ($target !== null) {
                $status->issues()->each(fn ($issue) => $issue->update(['status_id' => $target->id]));
            }

            $status->delete();
        }
    }

    /**
     * Close or reopen a status's issues after its closing flag changed.
     */
    private function syncIssueClosedTimestamps(Status $status): void
    {
        $issues = $status->issues();

        if ($status->is_closed) {
            $issues->whereNull('closed_at')->update(['closed_at' => now()]);
        } else {
            $issues->update(['closed_at' => null]);
        }
    }
}
