<?php

namespace App\Actions\Issues;

use App\Events\IssueCreated;
use App\Models\Board;
use App\Models\Issue;
use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateIssue
{
    /**
     * Create an issue at the bottom of its status column.
     *
     * Without a status, the issue goes in the board's first status.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Board $board, User $author, array $data): Issue
    {
        $statusId = $data['status_id'] ?? $board->statuses()->value('id');
        $lastSort = $board->issues()->where('status_id', $statusId)->max('sort');

        $labelIds = Arr::pull($data, 'label_ids', []);

        $issue = new Issue($data);
        $issue->label_names = Label::joinedNames($labelIds);
        $issue->board_id = $board->id;
        $issue->author_id = $author->id;
        $issue->status_id = $statusId;
        $issue->sort = ($lastSort ?? 0) + 1;
        $issue->save();
        $issue->labels()->sync($labelIds);
        Issue::reindex($labelIds === [] ? [] : [$issue->id]);

        IssueCreated::dispatch($issue);

        return $issue;
    }
}
