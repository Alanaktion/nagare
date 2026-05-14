<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Board extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const string TYPE_KANBAN = 'kanban';

    public const string TYPE_SCRUM = 'scrum';

    public const string ROLE_USER = 'user';

    public const string ROLE_ADMIN = 'admin';

    public const array TYPE_DESCRIPTIONS = [
        [
            'key' => 'kanban',
            'name' => 'Tasks only (Kanban)',
            'description' => 'A free-form collection of tasks.',
        ],
        [
            'key' => 'scrum',
            'name' => 'Stories + Tasks (Scrum)',
            'description' => 'A collection of tasks organized within stories.',
        ],
    ];

    protected $fillable = ['name', 'type'];

    protected $hidden = ['updated_at', 'deleted_at'];

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class)->orderBy('sort');
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class)->orderBy('start_date');
    }

    public function currentSprint(): ?Sprint
    {
        return $this->sprints()
            ->whereTodayOrBefore('start_date')
            ->whereTodayOrAfter('end_date')
            ->whereNull('closed_at')
            ->first();
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(['role']);
    }
}
