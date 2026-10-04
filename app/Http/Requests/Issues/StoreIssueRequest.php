<?php

namespace App\Http\Requests\Issues;

use App\Concerns\IssueValidationRules;
use App\Enums\IssueRole;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    use IssueValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('board'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Board $board */
        $board = $this->route('board');
        $allowedRoles = $board->has_stories ? [IssueRole::Story, IssueRole::Task] : [IssueRole::Task];
        $role = IssueRole::tryFrom((string) $this->input('role')) ?? IssueRole::Task;

        return [
            ...$this->issueRules($board, $role),
            'role' => ['required', Rule::enum(IssueRole::class)->only($allowedRoles)],
        ];
    }
}
