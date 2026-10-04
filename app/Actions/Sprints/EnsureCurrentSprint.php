<?php

namespace App\Actions\Sprints;

use App\Models\Board;
use App\Models\Sprint;

class EnsureCurrentSprint
{
    /**
     * Get the board's current sprint, creating it first if the board uses a
     * fixed calendar cycle and no sprint exists for today's period yet.
     *
     * Returns null for boards without sprints, custom-cycle boards with no
     * sprint covering today, and when today's sprint was already closed.
     */
    public function handle(Board $board): ?Sprint
    {
        $current = $board->currentSprint();

        if ($current !== null || ! $board->has_sprints || ! $board->sprint_cycle?->isAutomatic()) {
            return $current;
        }

        [$start, $end] = $board->sprint_cycle->periodFor(today());

        $sprint = $board->sprints()->firstOrCreate(
            ['slug' => $board->sprint_cycle->slugFor($start)],
            ['start_date' => $start, 'end_date' => $end],
        );

        return $sprint->isClosed() ? null : $sprint;
    }
}
