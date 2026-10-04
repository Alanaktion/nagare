<?php

namespace App\Actions\Issues;

use App\Models\Issue;

class UpdateIssue
{
    public function __construct(private RebalanceIssueSort $rebalanceSort) {}

    /**
     * Update an issue. Closing and reopening is handled by the model when
     * the status changes. Moving an issue within or between columns may
     * crowd the destination column's sort values, so it is rebalanced.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Issue $issue, array $data): Issue
    {
        $issue->update($data);

        if ($issue->wasChanged(['sort', 'status_id'])) {
            $this->rebalanceSort->handle($issue->board_id, $issue->status_id);
        }

        return $issue;
    }
}
