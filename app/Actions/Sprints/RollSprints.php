<?php

namespace App\Actions\Sprints;

use App\Models\Board;

class RollSprints
{
    public function __construct(
        private EnsureCurrentSprint $ensureCurrentSprint,
        private CloseSprint $closeSprint,
    ) {}

    /**
     * Start today's sprint on a fixed-cycle board and close sprints that
     * have ended, carrying their unfinished issues into today's sprint.
     * Boards with custom cycles are managed by hand and left alone.
     */
    public function handle(Board $board): void
    {
        if (! $board->has_sprints || ! $board->sprint_cycle?->isAutomatic()) {
            return;
        }

        $current = $this->ensureCurrentSprint->handle($board);

        $board->sprints()
            ->whereNull('closed_at')
            ->whereDate('end_date', '<', today()->toDateString())
            ->get()
            ->each(fn ($sprint) => $this->closeSprint->handle($sprint, $current));
    }
}
