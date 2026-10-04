<?php

namespace App\Http\Controllers;

use App\Enums\IssueRole;
use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\SprintResource;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The user's landing page: their most recently active boards, the open
     * issues assigned to them, and progress on the current sprint of each
     * board that uses sprints. The last two load after the page renders.
     */
    public function __invoke(#[CurrentUser] User $user): Response
    {
        return Inertia::render('Dashboard', [
            'boards' => BoardResource::collection(
                $user->boards()
                    ->withMax('issues', 'updated_at')
                    ->orderByDesc('issues_max_updated_at')
                    ->orderBy('name')
                    ->limit(6)
                    ->get()
            ),
            'assignedIssues' => Inertia::defer(fn () => IssueResource::collection(
                Issue::query()
                    ->where('assigned_id', $user->id)
                    ->whereNull('closed_at')
                    ->whereIn('board_id', $user->boards()->select('boards.id'))
                    ->with(['status', 'board'])
                    ->latest('updated_at')
                    ->limit(10)
                    ->get()
            )),
            'sprintSummaries' => Inertia::defer(fn () => $this->sprintSummaries($user)),
        ]);
    }

    /**
     * Task counts for the open sprint covering today on each of the user's
     * sprint boards. Boards without a current sprint are left out.
     *
     * @return array<int, array{board: BoardResource, sprint: SprintResource, total: int, done: int}>
     */
    private function sprintSummaries(User $user): array
    {
        $sprints = $user->boards()
            ->where('has_sprints', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($board) => ['board' => $board, 'sprint' => $board->currentSprint()])
            ->filter(fn (array $entry) => $entry['sprint'] !== null)
            ->values();

        $counts = Issue::query()
            ->whereIn('sprint_id', $sprints->pluck('sprint.id'))
            ->where('role', '!=', IssueRole::Story->value)
            ->selectRaw('sprint_id, count(*) as total, count(closed_at) as done')
            ->groupBy('sprint_id')
            ->get()
            ->keyBy('sprint_id');

        return $sprints->map(fn (array $entry) => [
            'board' => new BoardResource($entry['board']),
            'sprint' => new SprintResource($entry['sprint']),
            'total' => (int) ($counts[$entry['sprint']->id]->total ?? 0),
            'done' => (int) ($counts[$entry['sprint']->id]->done ?? 0),
        ])->all();
    }
}
