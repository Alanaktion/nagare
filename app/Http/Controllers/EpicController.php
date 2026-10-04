<?php

namespace App\Http\Controllers;

use App\Enums\IssueRole;
use App\Http\Resources\BoardResource;
use App\Http\Resources\EpicResource;
use App\Http\Resources\LabelResource;
use App\Http\Resources\UserResource;
use App\Models\Board;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EpicController extends Controller
{
    /**
     * List a board's epics with the progress of their stories' tasks.
     */
    public function index(#[CurrentUser] User $user, Board $board): Response
    {
        Gate::authorize('view', $board);
        abort_unless($board->has_stories, 404);

        $epics = $board->issues()
            ->where('role', IssueRole::Epic->value)
            ->with(['children' => fn ($stories) => $stories->orderBy('name')->with('children:id,parent_id,closed_at')])
            ->orderBy('name')
            ->get();

        return Inertia::render('epics/Index', [
            'board' => new BoardResource($board->load('statuses')->withRoleFor($user)),
            'epics' => EpicResource::collection($epics),
            'members' => UserResource::collection($board->users()->orderBy('name')->get()),
            'labels' => LabelResource::collection($board->labels()->get()),
        ]);
    }
}
