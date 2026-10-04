<?php

namespace App\Actions\Labels;

use App\Events\BoardUpdated;
use App\Models\Label;

class DeleteLabel
{
    /**
     * Delete a label, removing it from every issue that has it.
     */
    public function handle(Label $label): void
    {
        $label->delete();

        BoardUpdated::dispatch($label->board_id);
    }
}
