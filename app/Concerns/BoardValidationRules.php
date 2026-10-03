<?php

namespace App\Concerns;

use App\Enums\SprintCycle;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait BoardValidationRules
{
    /**
     * Get the validation rules used to validate board settings and statuses.
     *
     * Pass the board when updating so submitted status ids are restricted
     * to statuses that belong to it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function boardRules(?Board $board = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'has_stories' => ['boolean'],
            'has_sprints' => ['boolean'],
            'sprint_cycle' => ['nullable', 'required_if_accepted:has_sprints', Rule::enum(SprintCycle::class)],
            'statuses' => ['required', 'array', 'min:1', 'max:20'],
            'statuses.*.id' => $board === null
                ? ['prohibited']
                : ['nullable', 'integer', Rule::exists('statuses', 'id')->where('board_id', $board->id)->whereNull('deleted_at')],
            'statuses.*.name' => ['required', 'string', 'max:255'],
            'statuses.*.is_closed' => ['boolean'],
        ];
    }

    /**
     * Normalize checkbox-style inputs before validation, and clear the sprint
     * cycle when sprints are disabled.
     */
    protected function prepareBoardInput(): void
    {
        $hasSprints = $this->boolean('has_sprints');

        $this->merge([
            'has_stories' => $this->boolean('has_stories'),
            'has_sprints' => $hasSprints,
            'sprint_cycle' => $hasSprints ? $this->input('sprint_cycle') : null,
        ]);
    }
}
