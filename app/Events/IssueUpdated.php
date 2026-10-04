<?php

namespace App\Events;

use App\Http\Resources\IssueResource;
use App\Models\Issue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class IssueUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<int, float>|null  $renumberedSorts  New sort values by issue id, when the issue's column was renumbered.
     */
    public function __construct(public Issue $issue, public ?array $renumberedSorts = null)
    {
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('boards.'.$this->issue->board_id);
    }

    public function broadcastAs(): string
    {
        return 'issue.updated';
    }

    /**
     * @return array{issue: array<string, mixed>, sorts: array<int, float>|null}
     */
    public function broadcastWith(): array
    {
        return [
            'issue' => (new IssueResource($this->issue->load(['assignee', 'labels'])))->resolve(),
            'sorts' => $this->renumberedSorts,
        ];
    }
}
