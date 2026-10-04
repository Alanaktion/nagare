<?php

namespace App\Actions\Sprints;

use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\Sprint;
use Illuminate\Support\Carbon;

class CreateSprint
{
    /**
     * Manually create a sprint.
     *
     * On fixed-cycle boards the sprint covers the calendar period containing
     * `start_date`; on custom boards it runs from `start_date` to `end_date`.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Board $board, array $data): Sprint
    {
        $cycle = $board->sprintCycle();
        $period = $cycle->periodFor(Carbon::parse((string) $data['start_date']));
        $start = $period[0] ?? Carbon::parse((string) $data['start_date']);
        $end = $period[1] ?? Carbon::parse((string) $data['end_date']);

        $sprint = $board->sprints()->create([
            'slug' => $cycle->slugFor($start),
            'start_date' => $start,
            'end_date' => $end,
        ]);

        BoardUpdated::dispatch($board->id);

        return $sprint;
    }
}
