<?php

namespace App\Http\Requests\Issues;

use App\Concerns\IssueValidationRules;
use App\Models\Issue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateIssueRequest extends FormRequest
{
    use IssueValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('issue'));
    }

    /**
     * Fields are optional so clients can send partial updates.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Issue $issue */
        $issue = $this->route('issue');

        return $this->issueRules($issue->board ?? abort(404), $issue->role, partial: true, currentSprintId: $issue->sprint_id);
    }
}
