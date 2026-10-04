<?php

namespace App\Actions\Boards;

use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveBoardMember
{
    /**
     * Remove a member from a board and unassign the issues assigned to them.
     *
     * @throws ValidationException If they are the board's last admin.
     */
    public function handle(Board $board, User $member): void
    {
        DB::transaction(function () use ($board, $member): void {
            if ($board->isLastAdmin($member)) {
                throw ValidationException::withMessages([
                    'user' => __('A board needs at least one admin. Make someone else an admin first.'),
                ]);
            }

            $board->users()->detach($member);
            $assignedIds = $board->issues()->where('assigned_id', $member->id)->pluck('id')->all();
            $board->issues()->whereKey($assignedIds)->update(['assigned_id' => null]);
            Issue::reindex($assignedIds);
        });

        BoardUpdated::dispatch($board->id);
    }
}
