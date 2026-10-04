<?php

namespace App\Actions\Boards;

use App\Enums\IssueRole;
use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\LabelResource;
use App\Http\Resources\SprintResource;
use App\Http\Resources\UserResource;
use App\Models\Board;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class ShowBoard
{
    /**
     * Days a closed issue stays on views that aren't bounded by a sprint.
     */
    public const int CLOSED_ISSUE_DAYS = 14;

    /**
     * Render a board's issues. On boards with sprints this is one sprint,
     * or the backlog (issues without a sprint) when `$sprint` is null. Stories
     * are included wherever they have tasks, see `Issue::inSprintView()`.
     *
     * Kanban boards and backlogs grow forever, so they leave out tasks closed
     * more than `CLOSED_ISSUE_DAYS` ago unless `$withOlderClosed` is set.
     */
    public function handle(Board $board, User $viewer, ?Sprint $sprint = null, bool $withOlderClosed = false): Response
    {
        $issues = $board->issues()
            ->where('role', '!=', IssueRole::Epic->value)
            ->with(['assignee', 'labels'])
            ->withCount('children')
            ->orderBy('sort');

        if ($board->has_sprints) {
            $issues->inSprintView($sprint?->id);
        }

        $olderClosedCount = 0;

        if ($sprint === null) {
            $cutoff = now()->subDays(self::CLOSED_ISSUE_DAYS);

            $olderClosedCount = (clone $issues)
                ->where('role', '!=', IssueRole::Story->value)
                ->where('closed_at', '<', $cutoff)
                ->count();

            if (! $withOlderClosed) {
                $issues->where(fn (Builder $query) => $query
                    ->where('role', IssueRole::Story->value)
                    ->orWhereNull('closed_at')
                    ->orWhere('closed_at', '>=', $cutoff));
            }
        }

        return Inertia::render('boards/Show', [
            'board' => new BoardResource($board->load('statuses')->withRoleFor($viewer)),
            'issues' => IssueResource::collection($issues->get()),
            'members' => UserResource::collection($board->users()->orderBy('name')->get()),
            'labels' => LabelResource::collection($board->labels()->get()),
            'epics' => IssueResource::collection(
                $board->has_stories ? $board->issues()->where('role', IssueRole::Epic->value)->orderBy('name')->get() : collect()
            ),
            'sprint' => $sprint ? new SprintResource($sprint) : null,
            'sprints' => SprintResource::collection(
                $board->has_sprints ? $board->sprints()->reorder('start_date', 'desc')->limit(30)->get() : collect()
            ),
            'openSprints' => SprintResource::collection(
                $board->has_sprints ? $board->sprints()->whereNull('closed_at')->get() : collect()
            ),
            'previousSprint' => $sprint ? $this->neighbour($board, $sprint, '<') : null,
            'nextSprint' => $sprint ? $this->neighbour($board, $sprint, '>') : null,
            'olderClosedCount' => $olderClosedCount,
            'withOlderClosed' => $withOlderClosed,
            'closedIssueDays' => self::CLOSED_ISSUE_DAYS,
        ]);
    }

    private function neighbour(Board $board, Sprint $sprint, string $direction): ?SprintResource
    {
        $neighbour = $board->sprints()
            ->reorder('start_date', $direction === '<' ? 'desc' : 'asc')
            ->whereDate('start_date', $direction, $sprint->start_date->toDateString())
            ->first();

        return $neighbour ? new SprintResource($neighbour) : null;
    }
}
