<?php

namespace App\Models;

use App\Enums\BoardRole;
use App\Enums\SprintCycle;
use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property bool $has_stories
 * @property bool $has_sprints
 * @property SprintCycle|null $sprint_cycle
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'has_stories', 'has_sprints', 'sprint_cycle'])]
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_stories' => 'boolean',
            'has_sprints' => 'boolean',
            'sprint_cycle' => SprintCycle::class,
        ];
    }

    /**
     * @return HasMany<Status, $this>
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Boards the given user is a member of.
     *
     * @param  Builder<Board>  $query
     */
    #[Scope]
    protected function forMember(Builder $query, User $user): void
    {
        $query->whereHas('users', fn (Builder $members) => $members->whereKey($user->getKey()));
    }

    /**
     * Get the user's role on this board, or null if they are not a member.
     */
    public function roleFor(User $user): ?BoardRole
    {
        $role = $this->users()->whereKey($user->getKey())->value('board_user.role');

        return is_string($role) ? BoardRole::from($role) : null;
    }
}
