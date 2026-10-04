<?php

namespace App\Actions\Boards;

use App\Enums\BoardRole;
use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\User;

class AddBoardMember
{
    public function handle(Board $board, User $user, BoardRole $role): void
    {
        $board->users()->attach($user, ['role' => $role->value]);

        BoardUpdated::dispatch($board->id);
    }
}
