<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * The fewest characters a search needs before it looks anything up.
     */
    public const int MINIMUM_LENGTH = 2;

    /**
     * Issues per page of results.
     */
    public const int PER_PAGE = 20;

    /**
     * Search the boards and issues on the user's own boards. Results are
     * limited to those boards with a filter, so every Scout engine applies
     * the same access rules.
     */
    public function __invoke(Request $request, #[CurrentUser] User $user): Response
    {
        $query = trim($request->string('q')->toString());
        $isSearching = mb_strlen($query) >= self::MINIMUM_LENGTH;
        $boardIds = $user->boards()->pluck('boards.id');

        return Inertia::render('search/Index', [
            'query' => $query,
            'minimumLength' => self::MINIMUM_LENGTH,
            'boards' => BoardResource::collection(
                $isSearching
                    ? Board::search($query)
                        ->whereIn('id', $boardIds->map(strval(...))->all())
                        ->take(5)
                        ->get()
                    : []
            ),
            'issues' => IssueResource::collection(
                $isSearching
                    ? Issue::search($query)
                        ->whereIn('board_id', $boardIds->all())
                        ->query(fn (Builder $issues) => $issues->with(['status', 'board', 'labels', 'assignee']))
                        ->paginate(self::PER_PAGE)
                        ->withQueryString()
                    : new LengthAwarePaginator([], 0, self::PER_PAGE)
            ),
        ]);
    }
}
