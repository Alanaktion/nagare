<?php

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issue extends Model
{
    use BroadcastsEvents;
    use SoftDeletes;

    public const ROLE_EPIC = 'epic';

    public const ROLE_STORY = 'story';

    public const ROLE_TASK = 'task';

    public const ROLES = [self::ROLE_EPIC, self::ROLE_STORY, self::ROLE_TASK];

    protected $fillable = ['name', 'description', 'assigned_id', 'status_id', 'sort'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function __construct()
    {
        parent::__construct();

        static::updating(function (Issue $issue) {
            if ($issue->isDirty('status_id')) {
                if ($issue->status->is_closed) {
                    $issue->closed_at = now();
                } else {
                    $issue->closed_at = null;
                }
            }
        });
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn(string $event): array
    {
        return [
            new PrivateChannel('issues.'.$this->id),
            new PrivateChannel('boards.'.$this->board->id),
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
