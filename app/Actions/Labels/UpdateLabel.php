<?php

namespace App\Actions\Labels;

use App\Events\BoardUpdated;
use App\Models\Label;

class UpdateLabel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Label $label, array $data): Label
    {
        $label->update($data);

        BoardUpdated::dispatch($label->board_id);

        return $label;
    }
}
