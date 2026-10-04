<?php

namespace App\Http\Resources;

use App\Models\Sprint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sprint
 */
class SprintResource extends JsonResource
{
    /**
     * @return array{id: int, board_id: int, slug: string, start_date: string, end_date: string, closed_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'slug' => $this->slug,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ];
    }
}
