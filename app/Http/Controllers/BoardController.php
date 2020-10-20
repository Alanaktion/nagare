<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Story;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function show(Board $board)
    {
        // Redirect to current sprint for Scrum boards
        if ($board->type == 'scrum') {
            $sprint = Story::getSprint($board->sprint_cycle ?: 'weekly');
            return redirect("/boards/{$board->slug}/{$sprint}");
        }

        // Show Kanban boards
        return Inertia::render('Board', [
            'board' => $board,
        ]);
    }

    public function showSprint(Board $board, string $sprint)
    {
        // TODO: validate sprint format
        return Inertia::render('Board', [
            'board' => $board,
            'sprint' => $sprint,
        ]);
    }
}
