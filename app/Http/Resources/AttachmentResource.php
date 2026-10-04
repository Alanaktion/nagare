<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attachment
 */
class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_id' => $this->issue_id,
            'comment_id' => $this->comment_id,
            'name' => $this->name,
            'size' => $this->size,
            'mime_type' => $this->mime_type,
            'is_image' => $this->isImage(),
            'url' => route('attachments.show', $this->resource),
            'thumbnail_url' => $this->thumbnail_path === null ? null : route('attachments.thumbnail', $this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'can_delete' => (bool) $this->resource->getAttribute('can_delete'),
        ];
    }
}
