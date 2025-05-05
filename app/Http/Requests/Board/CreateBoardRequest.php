<?php

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;

class CreateBoardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'type' => 'required|in:scrum,kanban',
            'statuses' => 'required|array',
            'statuses.*' => 'required_array_keys:name,is_closed',
            'statuses.*.name' => 'required|distinct',
            'statuses.*.is_closed' => 'required|boolean',
            'sprint_cycle' => 'nullable,in:weekly,monthly,quarterly,custom',
        ];
    }
}
