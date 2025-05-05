<?php

namespace App\Http\Controllers;

use App\Http\Requests\Board\CreateBoardRequest;
use App\Http\Requests\Board\UpdateBoardRequest;
use App\Models\Board;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('board/Index', [
            'boards' => $request->user()->boards()
                ->with(['statuses', 'users', 'sprints'])
                ->get(),
            'boardTypes' => Board::TYPE_DESCRIPTIONS,
        ]);
    }

    public function show(Board $board)
    {
        if ($board->sprint_cycle) {
            $sprint = $board->currentSprint();

            return redirect()->route('boards.show-sprint', [$board, $sprint]);
        }

        // Show Kanban boards
        $board->load('statuses', 'users', 'issues', 'issues.assigned');
        if ($board->type == Board::TYPE_SCRUM) {
            $board->load('stories');
        }

        return Inertia::render('board/Show', [
            'board' => $board,
        ]);
    }

    public function create()
    {
        return Inertia::render('board/Create', [
            'boardTypes' => Board::TYPE_DESCRIPTIONS,
        ]);
    }

    public function store(CreateBoardRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $board = $user->boards()->create($request->validated());
        $sort = 1;
        foreach ($request->validated('statuses') as $status) {
            $board->statuses()->create([
                'name' => $status['name'],
                'sort' => $sort++,
                'is_closed' => $status['is_closed'],
            ]);
        }

        return redirect()->route('boards.show', $board);
    }

    public function update(Board $board, UpdateBoardRequest $request)
    {
        $board->update($request->validated());
        $sort = 1;
        foreach ($request->validated('statuses') as $status) {
            $board->statuses()->updateOrCreate([
                'id' => $status['id'],
            ], [
                'name' => $status['name'],
                'sort' => $sort++,
                'is_closed' => $status['is_closed'],
            ]);
        }

        return redirect()->route('boards.index');
    }

    public function destroy(Board $board)
    {
        Gate::authorize('delete', $board);
        $board->delete();

        return redirect()->route('boards.index');
    }

    public function showSprint(Board $board, Sprint $sprint)
    {
        // TODO: ensure sprint belongs to board!
        // TODO: load stories/issues filtered by sprint
        $board->load('statuses', 'users', 'issues', 'issues.assigned');

        return Inertia::render('board/Show', [
            'board' => $board,
            'sprint' => $sprint,
        ]);
    }
}
