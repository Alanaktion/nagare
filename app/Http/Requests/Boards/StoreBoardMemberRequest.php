<?php

namespace App\Http\Requests\Boards;

use App\Enums\BoardRole;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreBoardMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manageMembers', $this->route('board'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Board $board */
        $board = $this->route('board');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('board_user', 'user_id')->where('board_id', $board->id),
            ],
            'role' => ['required', Rule::enum(BoardRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['user_id.unique' => __('That user is already a member of this board.')];
    }
}
