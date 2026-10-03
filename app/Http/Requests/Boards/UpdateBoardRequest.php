<?php

namespace App\Http\Requests\Boards;

use App\Concerns\BoardValidationRules;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBoardRequest extends FormRequest
{
    use BoardValidationRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('board'));
    }

    protected function prepareForValidation(): void
    {
        $this->prepareBoardInput();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Board $board */
        $board = $this->route('board');

        return $this->boardRules($board);
    }
}
