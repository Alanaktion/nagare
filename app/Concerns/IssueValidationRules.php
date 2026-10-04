<?php

namespace App\Concerns;

use App\Enums\IssueRole;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait IssueValidationRules
{
    /**
     * Get the validation rules shared by issue creation and updates.
     *
     * Statuses, assignees, labels and parents are restricted to the board.
     * On boards with stories, a task may have a parent story and a story may
     * have a parent epic. Epics have no parent and no sprint. Issues go
     * into open sprints, though an existing issue may stay in its current
     * sprint after it closes. With `$partial`, name and status may be
     * omitted but never blank.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function issueRules(Board $board, IssueRole $role, bool $partial = false, ?int $currentSprintId = null): array
    {
        $parentRole = match (true) {
            ! $board->has_stories => null,
            $role === IssueRole::Task => IssueRole::Story,
            $role === IssueRole::Story => IssueRole::Epic,
            default => null,
        };

        return [
            'name' => [...($partial ? ['sometimes'] : []), 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status_id' => [...($partial ? ['sometimes', 'required'] : ['nullable']), 'integer', Rule::exists('statuses', 'id')->where('board_id', $board->id)->whereNull('deleted_at')],
            'sort' => ['sometimes', 'numeric', 'between:-1000000,1000000'],
            'sprint_id' => $board->has_sprints && $role !== IssueRole::Epic
                ? ['nullable', 'integer', Rule::exists('sprints', 'id')
                    ->where('board_id', $board->id)
                    ->where(fn (Builder $sprints) => $sprints->whereNull('closed_at')->orWhere('id', $currentSprintId))]
                : ['prohibited'],
            'label_ids' => ['sometimes', 'array', 'max:20'],
            'label_ids.*' => ['integer', 'distinct', Rule::exists('labels', 'id')->where('board_id', $board->id)],
            'assigned_id' => ['nullable', 'integer', Rule::exists('board_user', 'user_id')->where('board_id', $board->id)],
            'parent_id' => $parentRole !== null
                ? ['nullable', 'integer', Rule::exists('issues', 'id')
                    ->where('board_id', $board->id)
                    ->where('role', $parentRole->value)
                    ->whereNull('deleted_at')]
                : ['prohibited'],
        ];
    }
}
