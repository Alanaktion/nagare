<?php

namespace App\Events;

use App\Http\Resources\IssueResource;
use App\Models\Issue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class IssueCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Issue $issue)
    {
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('boards.'.$this->issue->board_id);
    }

    public function broadcastAs(): string
    {
        return 'issue.created';
    }

    /**
     * @return array{issue: array<string, mixed>}
     */
    public function broadcastWith(): array
    {
        return ['issue' => (new IssueResource($this->issue->load(['assignee', 'labels'])))->resolve()];
    }
}
