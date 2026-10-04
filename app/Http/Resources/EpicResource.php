<?php

namespace App\Http\Resources;

use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An epic with its stories, each rolled up to the tasks done and in total.
 * The epic needs its `children.children` loaded.
 *
 * @mixin Issue
 */
class EpicResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stories = $this->children->map(fn (Issue $story): array => [
            'id' => $story->id,
            'name' => $story->name,
            'closed_at' => $story->closed_at?->toIso8601String(),
            'tasks_done' => $story->children->whereNotNull('closed_at')->count(),
            'tasks_total' => $story->children->count(),
        ]);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'stories' => $stories->all(),
            'tasks_done' => $stories->sum('tasks_done'),
            'tasks_total' => $stories->sum('tasks_total'),
        ];
    }
}
