<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function showBoard(Board $board)
    {
        return $board->statuses()
            ->orderBy('sort')
            ->get();
    }
}
