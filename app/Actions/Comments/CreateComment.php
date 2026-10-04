<?php

namespace App\Actions\Comments;

use App\Actions\Attachments\StoreAttachments;
use App\Actions\Issues\WatchIssue;
use App\Actions\Notifications\NotifyIssueWatchers;
use App\Events\IssueTimelineChanged;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class CreateComment
{
    public function __construct(
        private StoreAttachments $storeAttachments,
        private WatchIssue $watchIssue,
        private NotifyIssueWatchers $notifyWatchers,
    ) {}

    /**
     * Add a comment, with any files posted along with it.
     *
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function handle(Issue $issue, User $author, array $data, array $files = []): Comment
    {
        $comment = new Comment(['body' => $data['body'] ?? '']);
        $comment->issue_id = $issue->id;
        $comment->user_id = $author->id;
        $comment->save();

        $this->storeAttachments->handle($issue, $author, $files, $comment);

        $this->watchIssue->handle($issue, $author);
        $this->notifyWatchers->forComment($issue, $comment, $author);

        IssueTimelineChanged::dispatch($issue->id, $issue->board_id);

        return $comment;
    }
}
