<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function showBoard(Board $board)
    {
        return $board->cards()
            ->with('assigned')
            ->orderBy('sort')
            ->get();
    }

    public function showSprint(Board $board, string $sprint)
    {
        return $board->cards()
            ->with('assigned')
            ->whereHas('story', function ($query) use ($sprint) {
                $query->where('sprint', $sprint);
            })
            ->orderBy('sort')
            ->get();
    }

    // TODO: actually test/use this function
    public function create(Board $board, Request $request)
    {
        $request->validate([
            'story_id' => 'sometimes|exists:stories,id',
            'name' => 'required|string',
            'description' => 'required|string',
            'sort' => 'sometimes|float',
        ]);
        return $board->cards()->create($request->all() + [
            'author_id' => $request->user()->id,
        ]);
    }
}
