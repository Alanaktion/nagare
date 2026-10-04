<?php

namespace App\Policies;

use App\Enums\BoardRole;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;

class CommentPolicy
{
    public function __construct(private IssuePolicy $issuePolicy) {}

    /**
     * Anyone who can update an issue can comment on it.
     */
    public function create(User $user, Issue $issue): bool
    {
        return $this->issuePolicy->update($user, $issue);
    }

    /**
     * Only a comment's author can edit it, and only while they are still on the board.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id && $this->canComment($user, $comment);
    }

    /**
     * Authors can delete their own comments, and board admins can delete any.
     */
    public function delete(User $user, Comment $comment): bool
    {
        $issue = $comment->issue;

        if ($issue === null || $issue->board === null) {
            return false;
        }

        return $this->update($user, $comment) || $issue->board->roleFor($user) === BoardRole::Admin;
    }

    private function canComment(User $user, Comment $comment): bool
    {
        return $comment->issue !== null && $this->create($user, $comment->issue);
    }
}
