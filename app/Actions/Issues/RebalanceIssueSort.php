<?php

namespace App\Actions\Issues;

use App\Models\Issue;

class RebalanceIssueSort
{
    /**
     * The smallest gap allowed between neighbouring sort values.
     */
    public const float MINIMUM_GAP = 0.0001;

    /**
     * Renumber a status column as 1, 2, 3... when fractional sorting has
     * left two neighbours too close together (or equal), keeping the order.
     *
     * @return array<int, float>|null The new sort value of every issue in the column, or null if nothing changed.
     */
    public function handle(int $boardId, int $statusId): ?array
    {
        $column = Issue::query()
            ->where('board_id', $boardId)
            ->where('status_id', $statusId)
            ->orderBy('sort')
            ->orderBy('id')
            ->pluck('sort', 'id');

        if (! $this->isCrowded($column->values()->all())) {
            return null;
        }

        $renumbered = [];
        $position = 1;

        foreach ($column->keys() as $id) {
            Issue::query()->whereKey($id)->toBase()->update(['sort' => $position]);
            $renumbered[$id] = (float) $position++;
        }

        return $renumbered;
    }

    /**
     * @param  array<int, float|int>  $sorts  Sort values in ascending order.
     */
    private function isCrowded(array $sorts): bool
    {
        for ($i = 1; $i < count($sorts); $i++) {
            if ($sorts[$i] - $sorts[$i - 1] < self::MINIMUM_GAP) {
                return true;
            }
        }

        return false;
    }
}
