<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

final class BoardPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Board $board): bool
    {
        return $this->userRole($user, $board) !== null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Board $board): bool
    {
        return $this->view($user, $board);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Board $board): bool
    {
        return $this->userRole($user, $board) === Board::ROLE_ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Board $board): bool
    {
        return $this->delete($user, $board);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Board $board): bool
    {
        return $this->delete($user, $board);
    }

    /**
     * Get the user's role within the board, or null if they are not a member
     */
    private function userRole(User $user, Board $board): ?string
    {
        $pivot = $board->users()->newPivotQuery()
            ->where('user_id', $user->id)
            ->select(['role'])
            ->first();

        return $pivot->role ?? null;
    }
}
