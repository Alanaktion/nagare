<?php

namespace App\Http\Controllers;

use App\Actions\Boards\ShowBoard;
use App\Models\Board;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class BacklogController extends Controller
{
    /**
     * Show the issues on a sprint board that aren't in any sprint.
     * `?closed=all` includes older closed issues.
     */
    public function show(Request $request, #[CurrentUser] User $user, Board $board, ShowBoard $showBoard): Response
    {
        Gate::authorize('view', $board);
        abort_unless($board->has_sprints, 404);

        return $showBoard->handle($board, $user, withOlderClosed: $request->query('closed') === 'all');
    }
}
