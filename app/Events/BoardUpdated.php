<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The board's settings or statuses changed, so viewers should reload it.
 */
class BoardUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $boardId)
    {
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('boards.'.$this->boardId);
    }

    public function broadcastAs(): string
    {
        return 'board.updated';
    }

    /**
     * @return array{id: int}
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->boardId];
    }
}
