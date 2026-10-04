<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\UserResource;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Browse and search all users.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('users/Index', [
            'users' => UserResource::collection(
                User::query()->matching($search)->orderBy('name')->paginate(24)->withQueryString()
            ),
            'search' => $search,
        ]);
    }

    /**
     * Show a user's profile: the boards and assigned issues they share with
     * the current user. Your own profile shows all of your boards.
     */
    public function show(Request $request, User $user): Response
    {
        $viewer = $request->user();

        $boards = $user->boards()
            ->when(! $viewer->is($user), fn ($boards) => $boards->forMember($viewer))
            ->orderBy('name')
            ->get();

        return Inertia::render('users/Show', [
            'profile' => new UserResource($user),
            'boards' => BoardResource::collection($boards),
            'assignedIssues' => Inertia::defer(fn () => IssueResource::collection(
                Issue::query()
                    ->where('assigned_id', $user->id)
                    ->whereIn('board_id', $boards->pluck('id'))
                    ->with(['status', 'board'])
                    ->orderByRaw('closed_at is not null')
                    ->latest('updated_at')
                    ->limit(100)
                    ->get()
            )),
        ]);
    }
}
