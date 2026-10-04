<?php

namespace App\Actions\Issues;

use App\Events\IssueDeleted;
use App\Models\Issue;

class DeleteIssue
{
    public function handle(Issue $issue): void
    {
        $issue->delete();

        IssueDeleted::dispatch($issue->id, $issue->board_id);
    }
}
