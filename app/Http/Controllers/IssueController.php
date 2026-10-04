<?php

namespace App\Http\Controllers;

use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\UpdateIssue;
use App\Http\Requests\Issues\StoreIssueRequest;
use App\Http\Requests\Issues\UpdateIssueRequest;
use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\UserResource;
use App\Models\Board;
use App\Models\Issue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function store(StoreIssueRequest $request, Board $board, CreateIssue $createIssue): RedirectResponse
    {
        $createIssue->handle($board, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Issue created.')]);

        return back();
    }

    public function show(Issue $issue): Response
    {
        Gate::authorize('view', $issue);

        $issue->load(['assignee', 'parent']);
        $board = $issue->board->load('statuses');

        return Inertia::render('issues/Show', [
            'issue' => new IssueResource($issue),
            'parent' => $issue->parent ? new IssueResource($issue->parent) : null,
            'board' => new BoardResource($board),
            'members' => UserResource::collection($board->users()->orderBy('name')->get()),
            'stories' => IssueResource::collection(
                $board->issues()->where('role', 'story')->orderBy('name')->get()
            ),
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue, UpdateIssue $updateIssue): RedirectResponse
    {
        $updateIssue->handle($issue, $request->validated());

        if ($request->hasAny(['name', 'description', 'assigned_id'])) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Issue updated.')]);
        }

        return back();
    }

    public function destroy(Issue $issue): RedirectResponse
    {
        Gate::authorize('delete', $issue);

        $issue->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Issue deleted.')]);

        return to_route('boards.show', $issue->board_id);
    }
}
