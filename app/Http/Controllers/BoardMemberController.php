<?php

namespace App\Http\Controllers;

use App\Actions\Boards\AddBoardMember;
use App\Actions\Boards\ChangeBoardMemberRole;
use App\Actions\Boards\RemoveBoardMember;
use App\Enums\BoardRole;
use App\Http\Requests\Boards\StoreBoardMemberRequest;
use App\Http\Requests\Boards\UpdateBoardMemberRequest;
use App\Models\Board;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BoardMemberController extends Controller
{
    public function store(StoreBoardMemberRequest $request, Board $board, AddBoardMember $addMember): RedirectResponse
    {
        $user = User::findOrFail($request->integer('user_id'));
        $addMember->handle($board, $user, $request->enum('role', BoardRole::class));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added to the board.', ['name' => $user->name])]);

        return back();
    }

    public function update(UpdateBoardMemberRequest $request, Board $board, User $member, ChangeBoardMemberRole $changeRole): RedirectResponse
    {
        abort_unless($board->roleFor($member) !== null, 404);

        $changeRole->handle($board, $member, $request->enum('role', BoardRole::class));

        return back();
    }

    /**
     * Remove a member, or let the current user leave the board.
     */
    public function destroy(Request $request, Board $board, User $member, RemoveBoardMember $removeMember): RedirectResponse
    {
        Gate::authorize('removeMember', [$board, $member]);
        abort_unless($board->roleFor($member) !== null, 404);

        $removeMember->handle($board, $member);

        if ($request->user()->is($member)) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('You left :board.', ['board' => $board->name])]);

            return to_route('boards.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name removed from the board.', ['name' => $member->name])]);

        return back();
    }
}
