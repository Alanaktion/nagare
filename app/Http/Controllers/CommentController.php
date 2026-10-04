<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateComment;
use App\Actions\Comments\DeleteComment;
use App\Actions\Comments\UpdateComment;
use App\Http\Requests\Comments\StoreCommentRequest;
use App\Http\Requests\Comments\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, #[CurrentUser] User $user, Issue $issue, CreateComment $createComment): RedirectResponse
    {
        $createComment->handle($issue, $user, $request->validated());

        return back();
    }

    public function update(UpdateCommentRequest $request, Comment $comment, UpdateComment $updateComment): RedirectResponse
    {
        $updateComment->handle($comment, $request->validated());

        return back();
    }

    public function destroy(Comment $comment, DeleteComment $deleteComment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $deleteComment->handle($comment);

        return back();
    }
}
