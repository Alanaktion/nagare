<?php

namespace App\Actions\Boards;

use App\Enums\BoardRole;
use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeBoardMemberRole
{
    /**
     * Change a member's role.
     *
     * @throws ValidationException If it would leave the board without an admin.
     */
    public function handle(Board $board, User $member, BoardRole $role): void
    {
        DB::transaction(function () use ($board, $member, $role): void {
            if ($role !== BoardRole::Admin && $board->isLastAdmin($member)) {
                throw ValidationException::withMessages([
                    'role' => __('A board needs at least one admin. Make someone else an admin first.'),
                ]);
            }

            $board->users()->updateExistingPivot($member->id, ['role' => $role->value]);
        });

        BoardUpdated::dispatch($board->id);
    }
}
