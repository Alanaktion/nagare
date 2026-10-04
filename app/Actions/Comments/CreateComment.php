<?php

namespace App\Actions\Comments;

use App\Events\IssueTimelineChanged;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;

class CreateComment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Issue $issue, User $author, array $data): Comment
    {
        $comment = new Comment($data);
        $comment->issue_id = $issue->id;
        $comment->user_id = $author->id;
        $comment->save();

        IssueTimelineChanged::dispatch($issue->id, $issue->board_id);

        return $comment;
    }
}
