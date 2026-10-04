<?php

namespace App\Actions\Labels;

use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\Label;

class CreateLabel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Board $board, array $data): Label
    {
        $label = $board->labels()->create($data);

        BoardUpdated::dispatch($board->id);

        return $label;
    }
}
