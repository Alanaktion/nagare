<?php

namespace App\Actions\Boards;

use App\Models\Board;
use Illuminate\Support\Facades\DB;

class UpdateBoard
{
    public function __construct(private SyncBoardStatuses $syncStatuses) {}

    /**
     * Update a board's settings and statuses.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Board $board, array $data): Board
    {
        return DB::transaction(function () use ($board, $data): Board {
            $board->update($data);
            $this->syncStatuses->handle($board, $data['statuses'], $data['status_moves'] ?? []);

            return $board;
        });
    }
}
