<?php

namespace App\Http\Requests\Boards;

use App\Concerns\BoardValidationRules;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreBoardRequest extends FormRequest
{
    use BoardValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('create', Board::class);
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
        return $this->boardRules();
    }
}
