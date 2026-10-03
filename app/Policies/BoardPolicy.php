<?php

namespace App\Policies;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    /**
     * Any authenticated user may list the boards they belong to.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only board members can view a board.
     */
    public function view(User $user, Board $board): bool
    {
        return $board->roleFor($user) !== null;
    }

    /**
     * Any authenticated user may create a board.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Any board member can update the board.
     */
    public function update(User $user, Board $board): bool
    {
        return $this->view($user, $board);
    }

    /**
     * Only board admins can delete a board.
     */
    public function delete(User $user, Board $board): bool
    {
        return $board->roleFor($user) === BoardRole::Admin;
    }

    /**
     * Only board admins can restore a board.
     */
    public function restore(User $user, Board $board): bool
    {
        return $this->delete($user, $board);
    }

    /**
     * Only board admins can permanently delete a board.
     */
    public function forceDelete(User $user, Board $board): bool
    {
        return $this->delete($user, $board);
    }
}
