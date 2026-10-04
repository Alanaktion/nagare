<?php

namespace App\Http\Requests\Issues;

use App\Concerns\IssueValidationRules;
use App\Models\Issue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueRequest extends FormRequest
{
    use IssueValidationRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('issue'));
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

        return $this->issueRules($issue->board, $issue->role, partial: true);
    }
}
