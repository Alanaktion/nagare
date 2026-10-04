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
use App\Http\Resources\UserResource;
use App\Models\Board;
use App\Models\User;
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
     * the backlog when there isn't one. `?closed=all` includes older closed issues.
     */
    public function show(Request $request, Board $board, EnsureCurrentSprint $ensureCurrentSprint, ShowBoard $showBoard): Response|RedirectResponse
    {
        Gate::authorize('view', $board);

        if (! $board->has_sprints) {
            return $showBoard->handle($board, withOlderClosed: $request->query('closed') === 'all');
        }

        $current = $ensureCurrentSprint->handle($board);

        return $current
            ? to_route('boards.sprints.show', [$board, $current])
            : to_route('boards.backlog', $board);
    }

    public function edit(Request $request, Board $board): Response
    {
        Gate::authorize('update', $board);

        return Inertia::render('boards/Edit', [
            'board' => new BoardResource(
                $board->load(['statuses' => fn ($statuses) => $statuses->withCount('issues')])->withRoleFor($request->user())
            ),
            'members' => UserResource::collection($board->users()->orderBy('name')->get()),
            'candidates' => Inertia::optional(fn () => $request->user()->can('manageMembers', $board)
                ? UserResource::collection(
                    User::query()
                        ->whereDoesntHave('boards', fn ($boards) => $boards->whereKey($board->id))
                        ->matching($request->string('search')->toString())
                        ->orderBy('name')
                        ->limit(10)
                        ->get()
                )
                : []),
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
