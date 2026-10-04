<?php

namespace App\Http\Controllers;

use App\Actions\Boards\ShowBoard;
use App\Actions\Sprints\CreateSprint;
use App\Http\Requests\Sprints\StoreSprintRequest;
use App\Models\Board;
use App\Models\Sprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SprintController extends Controller
{
    public function store(StoreSprintRequest $request, Board $board, CreateSprint $createSprint): RedirectResponse
    {
        $sprint = $createSprint->handle($board, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sprint :slug created.', ['slug' => $sprint->slug])]);

        return to_route('boards.sprints.show', [$board, $sprint]);
    }

    public function show(Board $board, Sprint $sprint, ShowBoard $showBoard): Response
    {
        Gate::authorize('view', $board);
        abort_unless($board->has_sprints, 404);

        return $showBoard->handle($board, $sprint);
    }
}
