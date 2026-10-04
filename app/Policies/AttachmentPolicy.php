<?php

namespace App\Policies;

use App\Enums\BoardRole;
use App\Models\Attachment;
use App\Models\Issue;
use App\Models\User;

class AttachmentPolicy
{
    public function __construct(private IssuePolicy $issuePolicy) {}

    /**
     * Anyone who can view an issue can see and download its attachments.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        return $attachment->issue !== null && $this->issuePolicy->view($user, $attachment->issue);
    }

    /**
     * Anyone who can update an issue can attach files to it.
     */
    public function create(User $user, Issue $issue): bool
    {
        return $this->issuePolicy->update($user, $issue);
    }

    /**
     * Whoever uploaded a file can delete it, and board admins can delete any.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        $issue = $attachment->issue;

        if ($issue === null || ! $this->issuePolicy->update($user, $issue)) {
            return false;
        }

        return $attachment->user_id === $user->id || $issue->board?->roleFor($user) === BoardRole::Admin;
    }
}
