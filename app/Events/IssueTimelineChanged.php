<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An issue's activity or comments changed. Open issue pages reload their
 * timeline, which is computed per viewer, so the event carries no content.
 */
class IssueTimelineChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $issueId, public int $boardId)
    {
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('boards.'.$this->boardId);
    }

    public function broadcastAs(): string
    {
        return 'issue.timeline.changed';
    }

    /**
     * @return array{id: int}
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->issueId];
    }
}
