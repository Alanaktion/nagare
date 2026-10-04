<?php

namespace App\Models;

use App\Enums\IssueRole;
use Carbon\CarbonInterface;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $board_id
 * @property int $status_id
 * @property int|null $parent_id
 * @property IssueRole $role
 * @property int|null $author_id
 * @property int|null $assigned_id
 * @property string $name
 * @property string|null $description
 * @property float $sort
 * @property CarbonInterface|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['status_id', 'parent_id', 'role', 'assigned_id', 'name', 'description', 'sort'])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Keep the closed timestamp in step with the issue's status, and detach
     * tasks from a story when the story is deleted.
     */
    protected static function booted(): void
    {
        static::saving(function (Issue $issue): void {
            if ($issue->exists && ! $issue->isDirty('status_id')) {
                return;
            }

            $isClosing = Status::query()->whereKey($issue->status_id)->value('is_closed');
            $issue->closed_at = $isClosing ? ($issue->closed_at ?? now()) : null;
        });

        static::deleting(function (Issue $issue): void {
            if (! $issue->isForceDeleting()) {
                $issue->children()->update(['parent_id' => null]);
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
            'role' => IssueRole::class,
            'sort' => 'float',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * @return BelongsTo<Status, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }
}
