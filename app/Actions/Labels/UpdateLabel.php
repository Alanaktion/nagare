<?php

namespace App\Actions\Labels;

use App\Events\BoardUpdated;
use App\Models\Issue;
use App\Models\Label;

class UpdateLabel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Label $label, array $data): Label
    {
        $label->update($data);

        if ($label->wasChanged('name')) {
            $label->issues()->each(fn (Issue $issue) => $issue->refreshLabelNames());
        }

        BoardUpdated::dispatch($label->board_id);

        return $label;
    }
}
