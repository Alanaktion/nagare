<?php

namespace App\Http\Requests\Issue;

use Illuminate\Foundation\Http\FormRequest;

class CreateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: authorize by board membership
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // TODO: verify status and sprint belong to correct board
        return [
            'assigned_id' => 'nullable|exists:users,id',
            'board_id' => 'required|exists:boards,id',
            'status_id' => 'exists:statuses,id',
            'sprint_id' => 'exists:sprints,id',
            'parent_id' => 'nullable|exists:issues,id',
            'name' => 'required|string|max:250',
            'description' => 'nullable|string',
            'sort' => 'nullable|numeric',
        ];
    }
}
