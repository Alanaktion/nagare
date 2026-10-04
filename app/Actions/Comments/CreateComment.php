<?php

namespace App\Actions\Comments;

use App\Actions\Issues\WatchIssue;
use App\Actions\Notifications\NotifyIssueWatchers;
use App\Events\IssueTimelineChanged;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;

class CreateComment
{
    public function __construct(
        private WatchIssue $watchIssue,
        private NotifyIssueWatchers $notifyWatchers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Issue $issue, User $author, array $data): Comment
    {
        $comment = new Comment($data);
        $comment->issue_id = $issue->id;
        $comment->user_id = $author->id;
        $comment->save();

        $this->watchIssue->handle($issue, $author);
        $this->notifyWatchers->forComment($issue, $comment, $author);

        IssueTimelineChanged::dispatch($issue->id, $issue->board_id);

        return $comment;
    }
}
