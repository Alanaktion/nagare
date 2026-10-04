<?php

namespace App\Http\Resources;

use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Board
 */
class BoardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'has_stories' => $this->has_stories,
            'has_sprints' => $this->has_sprints,
            'sprint_cycle' => $this->sprint_cycle?->value,
            'role' => $this->whenPivotLoaded(
                'board_user',
                fn () => $this->resource->getRelation('pivot')->role,
                fn () => $this->resource->getAttribute('current_role'),
            ),
            'statuses' => StatusResource::collection($this->whenLoaded('statuses')),
        ];
    }
}
