<?php

namespace App\Actions\Comments;

use App\Events\IssueTimelineChanged;
use App\Models\Comment;

class DeleteComment
{
    public function handle(Comment $comment): void
    {
        $issue = $comment->issue ?? abort(404);

        $comment->attachments()->delete();
        $comment->delete();

        IssueTimelineChanged::dispatch($issue->id, $issue->board_id);
    }
}
