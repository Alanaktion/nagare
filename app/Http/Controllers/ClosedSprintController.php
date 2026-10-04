<?php

namespace App\Http\Controllers;

use App\Actions\Sprints\CloseSprint;
use App\Http\Requests\Sprints\CloseSprintRequest;
use App\Models\Board;
use App\Models\Sprint;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ClosedSprintController extends Controller
{
    /**
     * Close a sprint, moving its unfinished issues to another sprint or the backlog.
     */
    public function store(CloseSprintRequest $request, Board $board, Sprint $sprint, CloseSprint $closeSprint): RedirectResponse
    {
        if (! $sprint->isClosed()) {
            $moveTo = $request->integer('move_to') ? Sprint::find($request->integer('move_to')) : null;
            $closeSprint->handle($sprint, $moveTo);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Sprint :slug closed.', ['slug' => $sprint->slug])]);
        }

        return to_route('boards.show', $board);
    }
}
