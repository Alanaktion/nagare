<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_id' => $this->issue_id,
            'body' => $this->body,
            'body_html' => $this->body_html,
            'created_at' => $this->created_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'can_update' => (bool) $this->resource->getAttribute('can_update'),
            'can_delete' => (bool) $this->resource->getAttribute('can_delete'),
        ];
    }
}
