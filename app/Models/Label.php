<?php

namespace App\Models;

use App\Enums\LabelColor;
use Carbon\CarbonImmutable;
use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $board_id
 * @property string $name
 * @property LabelColor $color
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'color'])]
class Label extends Model
{
    /** @use HasFactory<LabelFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => LabelColor::class,
        ];
    }

    /**
     * The names of the given labels in alphabetical order, as stored on an
     * issue for searching, or null when there are none.
     *
     * @param  array<int, int>  $ids
     */
    public static function joinedNames(array $ids): ?string
    {
        if ($ids === []) {
            return null;
        }

        return static::query()->whereKey($ids)->orderBy('name')->pluck('name')->implode(' ');
    }

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * @return BelongsToMany<Issue, $this>
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class);
    }
}
