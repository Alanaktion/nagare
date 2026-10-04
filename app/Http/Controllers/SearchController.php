<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * The fewest characters a search needs before it looks anything up.
     */
    public const int MINIMUM_LENGTH = 2;

    /**
     * Search the boards and issues on the user's own boards.
     */
    public function __invoke(Request $request, #[CurrentUser] User $user): Response
    {
        $query = trim($request->string('q')->toString());
        $isSearching = mb_strlen($query) >= self::MINIMUM_LENGTH;

        return Inertia::render('search/Index', [
            'query' => $query,
            'minimumLength' => self::MINIMUM_LENGTH,
            'boards' => BoardResource::collection(
                $isSearching ? $user->boards()->matching($query)->orderBy('name')->limit(5)->get() : []
            ),
            'issues' => IssueResource::collection(
                $isSearching
                    ? Issue::query()
                        ->whereIn('board_id', $user->boards()->select('boards.id'))
                        ->matching($query)
                        ->with(['status', 'board', 'labels', 'assignee'])
                        ->orderByRaw('closed_at is not null')
                        ->latest('updated_at')
                        ->paginate(20)
                        ->withQueryString()
                    : Issue::query()->whereRaw('0 = 1')->paginate(20)
            ),
        ]);
    }
}
