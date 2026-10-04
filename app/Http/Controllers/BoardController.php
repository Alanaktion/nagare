<?php

namespace App\Http\Controllers;

use App\Actions\Boards\CreateBoard;
use App\Actions\Boards\ShowBoard;
use App\Actions\Boards\UpdateBoard;
use App\Actions\Sprints\EnsureCurrentSprint;
use App\Enums\BoardRole;
use App\Events\BoardDeleted;
use App\Http\Requests\Boards\StoreBoardRequest;
use App\Http\Requests\Boards\UpdateBoardRequest;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    /**
     * List the user's boards, plus deleted boards they can restore.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('boards/Index', [
            'boards' => BoardResource::collection(
                $user->boards()->orderBy('name')->get()
            ),
            'archivedBoards' => BoardResource::collection(
                $user->boards()->onlyTrashed()
                    ->wherePivot('role', BoardRole::Admin->value)
                    ->orderBy('name')
                    ->get()
            ),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Board::class);

        return Inertia::render('boards/Create');
    }

    public function store(StoreBoardRequest $request, CreateBoard $createBoard): RedirectResponse
    {
        $board = $createBoard->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board created.')]);

        return to_route('boards.show', $board);
    }

    /**
     * Show a board. Boards with sprints open on the current sprint, or on
     * the backlog when there isn't one.
     */
    public function show(Board $board, EnsureCurrentSprint $ensureCurrentSprint, ShowBoard $showBoard): Response|RedirectResponse
    {
        Gate::authorize('view', $board);

        if (! $board->has_sprints) {
            return $showBoard->handle($board);
        }

        $current = $ensureCurrentSprint->handle($board);

        return $current
            ? to_route('boards.sprints.show', [$board, $current])
            : to_route('boards.backlog', $board);
    }

    public function edit(Board $board): Response
    {
        Gate::authorize('update', $board);

        return Inertia::render('boards/Edit', [
            'board' => new BoardResource($board->load(['statuses' => fn ($statuses) => $statuses->withCount('issues')])),
        ]);
    }

    public function update(UpdateBoardRequest $request, Board $board, UpdateBoard $updateBoard): RedirectResponse
    {
        $updateBoard->handle($board, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board updated.')]);

        return to_route('boards.show', $board);
    }

    public function destroy(Board $board): RedirectResponse
    {
        Gate::authorize('delete', $board);

        $board->delete();

        BoardDeleted::dispatch($board->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board deleted.')]);

        return to_route('boards.index');
    }
}
