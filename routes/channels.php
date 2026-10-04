<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('boards.{board}', function (User $user, Board $board) {
    return $user->can('view', $board);
});
