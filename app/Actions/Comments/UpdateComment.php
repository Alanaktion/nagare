<?php

namespace App\Actions\Comments;

use App\Events\IssueTimelineChanged;
use App\Models\Comment;

class UpdateComment
{
    /**
     * Change a comment's text. Saving it unchanged doesn't mark it as edited.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Comment $comment, array $data): Comment
    {
        $comment->fill($data);

        if ($comment->isDirty('body')) {
            $comment->edited_at = now();
            $comment->save();

            $issue = $comment->issue ?? abort(404);

            IssueTimelineChanged::dispatch($issue->id, $issue->board_id);
        }

        return $comment;
    }
}
