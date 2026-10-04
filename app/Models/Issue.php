<?php

namespace App\Models;

use App\Enums\IssueRole;
use Carbon\CarbonInterface;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int|null $sprint_id
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
#[Fillable(['status_id', 'sprint_id', 'parent_id', 'role', 'assigned_id', 'name', 'description', 'sort'])]
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
     * Issues shown on a sprint's board, or on the backlog when `$sprintId` is null.
     *
     * Tasks (and any issue that isn't a story) are shown when they are in the
     * sprint. A story is shown when it is assigned to the sprint itself or has
     * a task in it, so a story spanning several sprints appears in each. On
     * the backlog, a story is shown when it has a task there, or when it has
     * no sprint and no tasks at all (so a fresh story is never lost).
     *
     * @param  Builder<Issue>  $query
     */
    #[Scope]
    protected function inSprintView(Builder $query, ?int $sprintId): void
    {
        $inSprint = fn (Builder $issues) => $sprintId === null
            ? $issues->whereNull('sprint_id')
            : $issues->where('sprint_id', $sprintId);

        $query->where(function (Builder $view) use ($inSprint, $sprintId): void {
            $view->where(fn (Builder $others) => $inSprint($others->where('role', '!=', IssueRole::Story->value)))
                ->orWhere(function (Builder $stories) use ($inSprint, $sprintId): void {
                    $stories->where('role', IssueRole::Story->value)
                        ->where(function (Builder $story) use ($inSprint, $sprintId): void {
                            $story->whereHas('children', $inSprint);

                            $sprintId === null
                                ? $story->orWhere(fn (Builder $own) => $own->whereNull('sprint_id')->whereDoesntHave('children'))
                                : $story->orWhere('sprint_id', $sprintId);
                        });
                });
        });
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
     * @return BelongsTo<Sprint, $this>
     */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
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
