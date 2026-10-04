<?php

namespace App\Http\Requests\Boards;

use App\Enums\BoardRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBoardMemberRequest extends FormRequest
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
        return ['role' => ['required', Rule::enum(BoardRole::class)]];
    }
}
