<?php

namespace App\Http\Controllers;

use App\Actions\Boards\ShowBoard;
use App\Models\Board;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class BacklogController extends Controller
{
    /**
     * Show the issues on a sprint board that aren't in any sprint.
     */
    public function show(Board $board, ShowBoard $showBoard): Response
    {
        Gate::authorize('view', $board);
        abort_unless($board->has_sprints, 404);

        return $showBoard->handle($board);
    }
}
