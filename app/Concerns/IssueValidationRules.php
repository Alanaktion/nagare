<?php

namespace App\Concerns;

use App\Enums\IssueRole;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait IssueValidationRules
{
    /**
     * Get the validation rules shared by issue creation and updates.
     *
     * Statuses, assignees and parent stories are restricted to the board.
     * Only tasks on boards with stories may have a parent story. With
     * `$partial`, name and status may be omitted but never blank.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function issueRules(Board $board, IssueRole $role, bool $partial = false): array
    {
        $canHaveParent = $board->has_stories && $role === IssueRole::Task;

        return [
            'name' => [...($partial ? ['sometimes'] : []), 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status_id' => [...($partial ? ['sometimes', 'required'] : ['nullable']), 'integer', Rule::exists('statuses', 'id')->where('board_id', $board->id)->whereNull('deleted_at')],
            'sort' => ['sometimes', 'numeric', 'between:-1000000,1000000'],
            'assigned_id' => ['nullable', 'integer', Rule::exists('board_user', 'user_id')->where('board_id', $board->id)],
            'parent_id' => $canHaveParent
                ? ['nullable', 'integer', Rule::exists('issues', 'id')
                    ->where('board_id', $board->id)
                    ->where('role', IssueRole::Story->value)
                    ->whereNull('deleted_at')]
                : ['prohibited'],
        ];
    }
}
