<?php

namespace App\Http\Resources;

use App\Models\IssueActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IssueActivity
 */
class IssueActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'data' => $this->data ?? [],
            'created_at' => $this->created_at->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
