<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\LabelResource;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Scout\Builder as ScoutBuilder;

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
     * Search the boards and issues on the user's own boards, optionally
     * narrowed to one board, open or closed issues, issues assigned to the
     * user, or a label. Results are always limited to the user's boards with a
     * filter, so every Scout engine applies the same access rules.
     */
    public function __invoke(Request $request, #[CurrentUser] User $user): Response
    {
        $query = trim($request->string('q')->toString());
        $isSearching = mb_strlen($query) >= self::MINIMUM_LENGTH;

        $userBoards = $user->boards()->orderBy('name')->get(['boards.id', 'boards.name']);
        $boardIds = $userBoards->modelKeys();

        $board = $userBoards->firstWhere('id', $request->integer('board'));
        $labels = $board ? $board->labels()->get() : new Collection;
        $label = $labels->firstWhere('id', $request->integer('label'));
        $state = in_array($request->query('state'), ['open', 'closed'], true) ? $request->query('state') : null;
        $mine = $request->boolean('mine');

        return Inertia::render('search/Index', [
            'query' => $query,
            'minimumLength' => self::MINIMUM_LENGTH,
            'filters' => [
                'board' => $board?->id,
                'label' => $label?->id,
                'state' => $state,
                'mine' => $mine,
            ],
            'boardOptions' => $userBoards->map(fn (Board $option) => ['id' => $option->id, 'name' => $option->name])->all(),
            'labelOptions' => LabelResource::collection($labels),
            'boards' => BoardResource::collection(
                $isSearching
                    ? Board::search($query)
                        ->whereIn('id', array_map(strval(...), $boardIds))
                        ->take(5)
                        ->get()
                    : []
            ),
            'issues' => IssueResource::collection(
                $isSearching
                    ? $this->searchIssues($user, $query, $boardIds, $board, $label?->id, $state, $mine)
                        ->paginate(self::PER_PAGE)
                        ->withQueryString()
                    : new LengthAwarePaginator([], 0, self::PER_PAGE)
            ),
        ]);
    }

    /**
     * Build the issue search with its filters. Attributes every engine
     * indexes as real columns are filtered with Scout's `where`; the closed
     * state and labels live in an index attribute on search services, but are
     * constrained on the model's own query by the database engine.
     *
     * @param  array<int, int>  $boardIds
     * @return ScoutBuilder<Issue>
     */
    private function searchIssues(User $user, string $query, array $boardIds, ?Board $board, ?int $labelId, ?string $state, bool $mine): ScoutBuilder
    {
        $search = Issue::search($query);
        $constraints = [];

        $board ? $search->where('board_id', $board->id) : $search->whereIn('board_id', $boardIds);

        if ($mine) {
            $search->where('assigned_id', $user->id);
        }

        if ($state !== null) {
            if (Issue::searchesInDatabase()) {
                $constraints[] = fn (Builder $issues) => $state === 'closed' ? $issues->whereNotNull('closed_at') : $issues->whereNull('closed_at');
            } else {
                $search->where('is_closed', $state === 'closed');
            }
        }

        if ($labelId !== null) {
            if (Issue::searchesInDatabase()) {
                $constraints[] = fn (Builder $issues) => $issues->whereHas('labels', fn (Builder $labels) => $labels->whereKey($labelId));
            } else {
                $search->where('label_ids', $labelId);
            }
        }

        return $search->query(function (Builder $issues) use ($constraints): void {
            foreach ($constraints as $constrain) {
                $constrain($issues);
            }

            $issues->with(['status', 'board', 'labels', 'assignee']);
        });
    }
}
