<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    public function __construct(private BoardPolicy $boardPolicy) {}

    /**
     * Issues are visible to anyone who can view their board. Issues on a
     * deleted board are hidden until the board is restored.
     */
    public function view(User $user, Issue $issue): bool
    {
        return $issue->board !== null && $this->boardPolicy->view($user, $issue->board);
    }

    /**
     * Issues are editable by anyone who can update their board.
     */
    public function update(User $user, Issue $issue): bool
    {
        return $issue->board !== null && $this->boardPolicy->update($user, $issue->board);
    }

    /**
     * Issues are deletable by anyone who can update their board.
     */
    public function delete(User $user, Issue $issue): bool
    {
        return $this->update($user, $issue);
    }
}
