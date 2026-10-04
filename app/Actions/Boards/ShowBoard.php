<?php

namespace App\Actions\Boards;

use App\Http\Resources\BoardResource;
use App\Http\Resources\IssueResource;
use App\Http\Resources\SprintResource;
use App\Http\Resources\UserResource;
use App\Models\Board;
use App\Models\Sprint;
use Inertia\Inertia;
use Inertia\Response;

class ShowBoard
{
    /**
     * Render a board's issues. On boards with sprints this is one sprint,
     * or the backlog (issues without a sprint) when `$sprint` is null. Stories
     * are included wherever they have tasks, see `Issue::inSprintView()`.
     */
    public function handle(Board $board, ?Sprint $sprint = null): Response
    {
        $issues = $board->issues()->with('assignee')->withCount('children')->orderBy('sort');

        if ($board->has_sprints) {
            $issues->inSprintView($sprint?->id);
        }

        return Inertia::render('boards/Show', [
            'board' => new BoardResource($board->load('statuses')->withRoleFor(auth()->user())),
            'issues' => IssueResource::collection($issues->get()),
            'members' => UserResource::collection($board->users()->orderBy('name')->get()),
            'sprint' => $sprint ? new SprintResource($sprint) : null,
            'sprints' => SprintResource::collection(
                $board->has_sprints ? $board->sprints()->reorder('start_date', 'desc')->limit(30)->get() : collect()
            ),
            'openSprints' => SprintResource::collection(
                $board->has_sprints ? $board->sprints()->whereNull('closed_at')->get() : collect()
            ),
            'previousSprint' => $sprint ? $this->neighbour($board, $sprint, '<') : null,
            'nextSprint' => $sprint ? $this->neighbour($board, $sprint, '>') : null,
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
