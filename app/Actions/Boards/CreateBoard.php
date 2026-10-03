<?php

namespace App\Actions\Boards;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBoard
{
    public function __construct(private SyncBoardStatuses $syncStatuses) {}

    /**
     * Create a board with its statuses and attach the creator as an admin.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $creator, array $data): Board
    {
        return DB::transaction(function () use ($creator, $data): Board {
            $board = new Board($data);
            $board->created_by = $creator->id;
            $board->save();

            $board->users()->attach($creator, ['role' => BoardRole::Admin->value]);
            $this->syncStatuses->handle($board, $data['statuses']);

            return $board;
        });
    }
}
