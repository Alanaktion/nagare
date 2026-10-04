<?php

namespace App\Actions\Attachments;

use App\Actions\Issues\RecordIssueActivity;
use App\Enums\IssueActivityType;
use App\Events\IssueTimelineChanged;
use App\Models\Attachment;
use App\Models\User;

class DeleteAttachment
{
    public function __construct(private RecordIssueActivity $recordActivity) {}

    /**
     * Delete an attachment. It disappears at once, but its files stay on disk
     * until `attachments:prune` removes them.
     */
    public function handle(Attachment $attachment, ?User $actor): void
    {
        $issue = $attachment->issue ?? abort(404);

        $attachment->delete();

        if ($attachment->comment_id === null) {
            $this->recordActivity->handle($issue, $actor, [[IssueActivityType::AttachmentRemoved, ['name' => $attachment->name]]]);
        } else {
            IssueTimelineChanged::dispatch($issue->id, $issue->board_id);
        }
    }
}
