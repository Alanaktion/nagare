<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RestoredBoardController extends Controller
{
    /**
     * Restore a soft-deleted board.
     */
    public function store(Board $board): RedirectResponse
    {
        Gate::authorize('restore', $board);

        $board->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board restored.')]);

        return to_route('boards.show', $board);
    }
}
