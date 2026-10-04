<?php

namespace App\Actions\Sprints;

use App\Events\BoardUpdated;
use App\Models\Sprint;
use Illuminate\Support\Facades\DB;

class CloseSprint
{
    /**
     * Close a sprint. Its unfinished issues move to `$moveTo`, or back to
     * the backlog when it is null. Finished issues stay with the sprint.
     */
    public function handle(Sprint $sprint, ?Sprint $moveTo = null): void
    {
        DB::transaction(function () use ($sprint, $moveTo): void {
            $sprint->issues()->whereNull('closed_at')->update(['sprint_id' => $moveTo?->id]);
            $sprint->update(['closed_at' => now()]);
        });

        BoardUpdated::dispatch($sprint->board_id);
    }
}
