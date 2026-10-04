<?php

namespace App\Actions\Labels;

use App\Events\BoardUpdated;
use App\Models\Issue;
use App\Models\Label;

class DeleteLabel
{
    /**
     * Delete a label, removing it from every issue that has it.
     */
    public function handle(Label $label): void
    {
        $issues = $label->issues()->get();

        $label->delete();

        $issues->each(fn (Issue $issue) => $issue->refreshLabelNames());

        BoardUpdated::dispatch($label->board_id);
    }
}
