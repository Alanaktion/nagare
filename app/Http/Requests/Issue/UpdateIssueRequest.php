<?php

namespace App\Http\Requests\Issue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\Issue $issue */
        $issue = $this->route()->parameter('issue');

        return Gate::check('update', $issue->board);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_id' => 'nullable|exists:users,id',
            'status_id' => 'exists:statuses,id',
            'sprint_id' => 'exists:sprints,id',
            'parent_id' => 'nullable|exists:issues,id',
            'name' => 'string|max:250',
            'description' => 'nullable|string',
            'sort' => 'nullable|numeric',
        ];
    }
}
