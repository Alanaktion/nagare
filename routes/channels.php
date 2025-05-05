<?php

use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('boards.{board}', function (User $user, Board $board) {
    return Gate::forUser($user)->check('view', $board);
});
Broadcast::channel('issues.{issue}', function (User $user, Issue $issue) {
    return Gate::forUser($user)->check('view', $issue);
});
