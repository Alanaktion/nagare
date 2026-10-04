<?php

namespace App\Actions\Attachments;

use App\Actions\Issues\RecordIssueActivity;
use App\Enums\IssueActivityType;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Str;
use Throwable;

class StoreAttachments
{
    /**
     * The width, in pixels, of a picture's thumbnail.
     */
    public const int THUMBNAIL_WIDTH = 320;

    public const int THUMBNAIL_QUALITY = 80;

    public function __construct(private RecordIssueActivity $recordActivity) {}

    /**
     * Store uploaded files on an issue, or on one of its comments. Pictures
     * also get a thumbnail. Files attached straight to the issue are noted in
     * its timeline; files in a comment appear with the comment.
     *
     * @param  list<UploadedFile>  $files
     * @return list<Attachment>
     */
    public function handle(Issue $issue, User $uploader, array $files, ?Comment $comment = null): array
    {
        $disk = (string) config('attachments.disk');
        $attachments = [];

        foreach ($files as $file) {
            $mimeType = (string) $file->getMimeType();
            $directory = "attachments/{$issue->id}";
            $name = (string) Str::ulid();
            $extension = strtolower($file->getClientOriginalExtension());

            $path = $file->storeAs($directory, $extension === '' ? $name : "{$name}.{$extension}", ['disk' => $disk]);

            if ($path === false) {
                continue;
            }

            $attachments[] = $issue->attachments()->create([
                'comment_id' => $comment?->id,
                'user_id' => $uploader->id,
                'disk' => $disk,
                'path' => $path,
                'thumbnail_path' => $this->thumbnail($file, $mimeType, $directory, $name, $disk),
                'name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'mime_type' => $mimeType,
                'size' => $file->getSize() ?: 0,
            ]);
        }

        if ($comment === null && $attachments !== []) {
            $this->recordActivity->handle($issue, $uploader, [[
                IssueActivityType::Attached,
                ['names' => array_map(fn (Attachment $attachment) => $attachment->name, $attachments)],
            ]]);
        }

        return $attachments;
    }

    /**
     * A small WebP copy of a picture, or null for other files, for pictures
     * that are too large to decode safely, and when it can't be made.
     */
    private function thumbnail(UploadedFile $file, string $mimeType, string $directory, string $name, string $disk): ?string
    {
        if (! in_array($mimeType, (array) config('attachments.inline_mime_types'), true)) {
            return null;
        }

        $size = @getimagesize($file->getRealPath());

        if ($size === false || (int) config('attachments.max_thumbnail_pixels') < $size[0] * $size[1]) {
            return null;
        }

        try {
            $path = Image::fromUpload($file)
                ->orient()
                ->scale(width: self::THUMBNAIL_WIDTH)
                ->toWebp()
                ->quality(self::THUMBNAIL_QUALITY)
                ->storeAs($directory, "{$name}-thumbnail.webp", $disk);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        return $path === false ? null : $path;
    }
}
