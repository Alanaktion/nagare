<?php

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateBoardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $board = $this->route()->parameter('board');

        return Gate::check('update', $board);
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
        ];
    }
}
