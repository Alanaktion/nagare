<?php

namespace App\Actions\Issues;

use App\Models\Issue;
use App\Models\User;

class WatchIssue
{
    /**
     * Start notifying a user about changes to an issue. Does nothing if they
     * already watch it.
     */
    public function handle(Issue $issue, User $user): void
    {
        $issue->watchers()->syncWithoutDetaching([$user->id]);
    }
}
