<?php

namespace App\Actions\Issues;

use App\Models\Issue;

class UpdateIssue
{
    /**
     * Update an issue. Closing and reopening is handled by the model when
     * the status changes.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Issue $issue, array $data): Issue
    {
        $issue->update($data);

        return $issue;
    }
}
