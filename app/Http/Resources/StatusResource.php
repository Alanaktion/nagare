<?php

namespace App\Http\Resources;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Status
 */
class StatusResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, sort: int, is_closed: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sort' => $this->sort,
            'is_closed' => $this->is_closed,
        ];
    }
}
