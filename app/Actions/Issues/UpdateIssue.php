<?php

namespace App\Actions\Issues;

use App\Actions\Notifications\NotifyIssueWatchers;
use App\Events\IssueUpdated;
use App\Models\Issue;
use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Arr;

class UpdateIssue
{
    public function __construct(
        private RebalanceIssueSort $rebalanceSort,
        private RecordIssueActivity $recordActivity,
        private NotifyIssueWatchers $notifyWatchers,
    ) {}

    /**
     * Update an issue. Closing and reopening is handled by the model when
     * the status changes. Moving an issue within or between columns may
     * crowd the destination column's sort values, so it is rebalanced.
     *
     * @param  array<string, mixed>  $data
     * @param  User|null  $actor  Who made the change, for the issue's timeline.
     */
    public function handle(Issue $issue, array $data, ?User $actor = null): Issue
    {
        $labelIds = Arr::pull($data, 'label_ids');

        $before = [
            'name' => $issue->name,
            'status_id' => $issue->status_id,
            'assigned_id' => $issue->assigned_id,
            'sprint_id' => $issue->sprint_id,
            'closed' => $issue->closed_at !== null,
        ];
        $descriptionBefore = (string) $issue->description;
        $labelIdsBefore = $labelIds === null ? null : $issue->labels()->pluck('labels.id')->all();

        $issue->fill($data);

        if ($labelIds !== null) {
            $issue->label_names = Label::joinedNames($labelIds);
        }

        $issue->save();

        if ($labelIds !== null) {
            $issue->labels()->sync($labelIds);
            Issue::reindex([$issue->id]);
        }

        $entries = $this->recordActivity->forUpdate($issue, $before, $labelIdsBefore, $labelIds === null ? null : array_map(intval(...), $labelIds), $actor);

        $this->notifyWatchers->forUpdate($issue, $entries, (string) $issue->description !== $descriptionBefore, $actor);

        $renumbered = $issue->wasChanged(['sort', 'status_id'])
            ? $this->rebalanceSort->handle($issue->board_id, $issue->status_id)
            : null;

        IssueUpdated::dispatch($issue, $renumbered);

        return $issue;
    }
}
