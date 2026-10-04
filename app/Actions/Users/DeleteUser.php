<?php

namespace App\Actions\Users;

use App\Enums\BoardRole;
use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    /**
     * Delete a user's account without leaving boards that nobody can manage.
     *
     * Where the user is a board's only admin, the longest-standing other
     * member becomes an admin. Boards with no other members are deleted,
     * which keeps them restorable from the database.
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->boards()
                ->withTrashed()
                ->wherePivot('role', BoardRole::Admin->value)
                ->get()
                ->filter(fn (Board $board) => $board->isLastAdmin($user))
                ->each(fn (Board $board) => $this->handOver($board, $user));

            $user->notifications()->delete();
            $user->delete();
        });
    }

    private function handOver(Board $board, User $leaving): void
    {
        $successor = $board->users()
            ->whereKeyNot($leaving->id)
            ->orderBy('board_user.created_at')
            ->orderBy('board_user.id')
            ->first();

        if ($successor === null) {
            $board->delete();

            return;
        }

        $board->users()->updateExistingPivot($successor->id, ['role' => BoardRole::Admin->value]);

        BoardUpdated::dispatch($board->id);
    }
}
