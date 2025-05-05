<?php

namespace App\Http\Controllers;

use App\Http\Requests\Issue\CreateIssueRequest;
use App\Http\Requests\Issue\UpdateIssueRequest;
use App\Models\Issue;
use App\Models\Status;
use Inertia\Inertia;

class IssueController extends Controller
{
    /**
     * Display a listing of issues.
     */
    public function index()
    {
        // TODO: Do we have a listing of all issues assigned to the user?
        // What could be most helpful?
    }

    /**
     * Show the form for creating a new issue.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created issue in storage.
     */
    public function store(CreateIssueRequest $request, Issue $issue)
    {
        /** @var \App\Models\User */
        $user = $request->user();
        $data = $request->validated();
        if (! $request->has('status_id')) {
            $data['status_id'] = Status::query()
                ->where('board_id', $request->input('board_id'))
                ->orderBy('sort')
                ->first(['id'])
                ->id;
        }
        $issue = $user->createdIssues()->forceCreate($data);
        if ($request->expectsJson()) {
            return $issue;
        }

        return redirect()->route('issues.show', $issue);
    }

    /**
     * Display the issue.
     */
    public function show(Issue $issue)
    {
        $issue->load('board', 'board.users', 'board.statuses', 'author', 'assigned', 'status');

        return Inertia::render('board/issue/Show', [
            'issue' => $issue,
        ]);
    }

    /**
     * Update the issue in storage.
     */
    public function update(UpdateIssueRequest $request, Issue $issue)
    {
        $issue->update($request->validated());
        if ($request->expectsJson()) {
            return $issue;
        }

        return redirect()->route('issues.show', $issue);
    }

    /**
     * Remove the issue from storage.
     */
    public function destroy(Issue $issue)
    {
        //
    }
}
