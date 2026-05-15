<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Issue extends Model
{
    use BroadcastsEvents;
    use HasFactory;
    use SoftDeletes;

    public const string ROLE_EPIC = 'epic';

    public const string ROLE_STORY = 'story';

    public const string ROLE_TASK = 'task';

    public const array ROLES = [self::ROLE_EPIC, self::ROLE_STORY, self::ROLE_TASK];

    protected $fillable = ['name', 'description', 'assigned_id', 'status_id', 'sort'];

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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function assigned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    protected static function booted(): void
    {
        self::updating(function (Issue $issue): void {
            if ($issue->isDirty('status_id')) {
                $issue->closed_at = $issue->status->is_closed ? now() : null;
            }
        });
    }

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
}
