<?php

namespace App\Actions\Issues;

use App\Enums\IssueActivityType;
use App\Events\IssueTimelineChanged;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\User;

class RecordIssueActivity
{
    /**
     * Record that an issue was created.
     */
    public function forCreate(Issue $issue, ?User $actor): void
    {
        $this->handle($issue, $actor, [[IssueActivityType::Created, ['name' => $issue->name]]]);
    }

    /**
     * Record what an update changed, comparing the issue with how it was
     * before. Moving an issue to or from a closing status is recorded as
     * closing or reopening it rather than as a plain move. Changes that only
     * reorder a column, or that leave a value as it was, record nothing.
     *
     * @param  array{name: string, status_id: int, assigned_id: int|null, sprint_id: int|null, closed: bool}  $before
     * @param  array<int, int>|null  $labelIdsBefore
     * @param  array<int, int>|null  $labelIdsAfter  Null when the update didn't touch labels.
     * @return list<array{0: IssueActivityType, 1: array<string, mixed>}> What was recorded.
     */
    public function forUpdate(Issue $issue, array $before, ?array $labelIdsBefore, ?array $labelIdsAfter, ?User $actor): array
    {
        $entries = [];

        if ($issue->name !== $before['name']) {
            $entries[] = [IssueActivityType::Renamed, ['from' => $before['name'], 'to' => $issue->name]];
        }

        if ($issue->status_id !== $before['status_id']) {
            $names = Status::withTrashed()->whereKey([$before['status_id'], $issue->status_id])->pluck('name', 'id');
            $isClosed = $issue->closed_at !== null;
            $type = match (true) {
                $isClosed && ! $before['closed'] => IssueActivityType::Closed,
                ! $isClosed && $before['closed'] => IssueActivityType::Reopened,
                default => IssueActivityType::Moved,
            };

            $entries[] = [$type, ['from' => $names[$before['status_id']] ?? null, 'to' => $names[$issue->status_id] ?? null]];
        }

        if ($issue->assigned_id !== $before['assigned_id']) {
            $names = User::query()->whereKey(array_filter([$before['assigned_id'], $issue->assigned_id]))->pluck('name', 'id');

            $entries[] = [IssueActivityType::Assigned, [
                'from' => $before['assigned_id'] === null ? null : ($names[$before['assigned_id']] ?? null),
                'to' => $issue->assigned_id === null ? null : ($names[$issue->assigned_id] ?? null),
            ]];
        }

        if ($issue->sprint_id !== $before['sprint_id']) {
            $slugs = Sprint::query()->whereKey(array_filter([$before['sprint_id'], $issue->sprint_id]))->pluck('slug', 'id');

            $entries[] = [IssueActivityType::SprintChanged, [
                'from' => $before['sprint_id'] === null ? null : ($slugs[$before['sprint_id']] ?? null),
                'to' => $issue->sprint_id === null ? null : ($slugs[$issue->sprint_id] ?? null),
            ]];
        }

        if ($labelIdsBefore !== null && $labelIdsAfter !== null) {
            $added = array_values(array_diff($labelIdsAfter, $labelIdsBefore));
            $removed = array_values(array_diff($labelIdsBefore, $labelIdsAfter));

            if ($added !== [] || $removed !== []) {
                $names = Label::query()->whereKey([...$added, ...$removed])->pluck('name', 'id');

                $entries[] = [IssueActivityType::LabelsChanged, [
                    'added' => array_values(array_map(fn (int $id) => $names[$id], array_filter($added, fn (int $id) => $names->has($id)))),
                    'removed' => array_values(array_map(fn (int $id) => $names[$id], array_filter($removed, fn (int $id) => $names->has($id)))),
                ]];
            }
        }

        $this->handle($issue, $actor, $entries);

        return $entries;
    }

    /**
     * Record entries in an issue's timeline and tell open pages about them.
     *
     * @param  list<array{0: IssueActivityType, 1: array<string, mixed>}>  $entries
     */
    public function handle(Issue $issue, ?User $actor, array $entries): void
    {
        if ($entries === []) {
            return;
        }

        foreach ($entries as [$type, $data]) {
            $issue->activities()->create([
                'user_id' => $actor?->id,
                'type' => $type,
                'data' => $data === [] ? null : $data,
            ]);
        }

        IssueTimelineChanged::dispatch($issue->id, $issue->board_id);
    }
}
