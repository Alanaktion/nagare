<?php

namespace App\Http\Resources;

use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Issue
 */
class IssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'status_id' => $this->status_id,
            'parent_id' => $this->parent_id,
            'role' => $this->role->value,
            'name' => $this->name,
            'description' => $this->description,
            'sort' => $this->sort,
            'author_id' => $this->author_id,
            'assigned_id' => $this->assigned_id,
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ];
    }
}
