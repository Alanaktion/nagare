<?php

namespace App\Console\Commands;

use App\Actions\Sprints\RollSprints as RollBoardSprints;
use App\Models\Board;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sprints:roll')]
#[Description('Start the current sprint on fixed-cycle boards and close sprints that have ended')]
class RollSprints extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RollBoardSprints $rollSprints): int
    {
        $count = 0;

        Board::query()->where('has_sprints', true)->each(function (Board $board) use ($rollSprints, &$count): void {
            $rollSprints->handle($board);
            $count++;
        });

        $this->info("Rolled sprints on {$count} board(s).");

        return self::SUCCESS;
    }
}
