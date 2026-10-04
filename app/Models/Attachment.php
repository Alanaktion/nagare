<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * A file on an issue, or on one of its comments. Deleting it hides it at once
 * and the files are removed later by `attachments:prune`.
 *
 * @property int $id
 * @property int $issue_id
 * @property int|null $comment_id
 * @property int|null $user_id
 * @property string $disk
 * @property string $path
 * @property string|null $thumbnail_path
 * @property string $name
 * @property string $mime_type
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory, SoftDeletes;

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
            'size' => 'integer',
        ];
    }

    /**
     * Whether it is a picture that browsers can show.
     */
    public function isImage(): bool
    {
        return in_array($this->mime_type, (array) config('attachments.inline_mime_types'), true);
    }

    /**
     * Remove the stored file and thumbnail from their disk.
     */
    public function deleteFiles(): void
    {
        $paths = array_filter([$this->path, $this->thumbnail_path]);

        if ($paths !== []) {
            Storage::disk($this->disk)->delete($paths);
        }
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Who uploaded it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
