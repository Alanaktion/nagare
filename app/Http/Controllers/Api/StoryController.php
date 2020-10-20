<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function showSprint(Board $board, string $sprint)
    {
        return $board->stories()
            ->with('assigned')
            ->where('sprint', $sprint)
            ->orderBy('sort')
            ->get();
    }
}
