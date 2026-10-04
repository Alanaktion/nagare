<?php

namespace App\Http\Controllers;

use App\Actions\Issues\UnwatchIssue;
use App\Actions\Issues\WatchIssue;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IssueWatcherController extends Controller
{
    /**
     * Start watching an issue as the current user.
     */
    public function store(#[CurrentUser] User $user, Issue $issue, WatchIssue $watchIssue): RedirectResponse
    {
        Gate::authorize('view', $issue);

        $watchIssue->handle($issue, $user);

        return back();
    }

    /**
     * Stop watching an issue as the current user.
     */
    public function destroy(#[CurrentUser] User $user, Issue $issue, UnwatchIssue $unwatchIssue): RedirectResponse
    {
        Gate::authorize('view', $issue);

        $unwatchIssue->handle($issue, $user);

        return back();
    }
}
