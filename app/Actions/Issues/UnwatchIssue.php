<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\User;

class UnwatchIssue
{
    public function handle(Issue $issue, User $user): void
    {
        $issue->watchers()->detach($user->id);
    }
}
