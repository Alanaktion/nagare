<?php

namespace App\Models;

use App\Enums\IssueActivityType;
use Carbon\CarbonImmutable;
use Database\Factories\IssueActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded change to an issue. The names of the things it refers to are
 * stored in `data`, so the entry still reads correctly after they are renamed
 * or deleted.
 *
 * @property int $id
 * @property int $issue_id
 * @property int|null $user_id
 * @property IssueActivityType $type
 * @property array<string, mixed>|null $data
 * @property CarbonImmutable $created_at
 */
class IssueActivity extends Model
{
    /** @use HasFactory<IssueActivityFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IssueActivityType::class,
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
