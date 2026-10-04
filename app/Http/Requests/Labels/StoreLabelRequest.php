<?php

namespace App\Http\Requests\Labels;

use App\Concerns\LabelValidationRules;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreLabelRequest extends FormRequest
{
    use LabelValidationRules;

    /**
     * The most labels a board can have.
     */
    public const int MAXIMUM_PER_BOARD = 50;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('board'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Board $board */
        $board = $this->route('board');

        return $this->labelRules($board);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Board $board */
            $board = $this->route('board');

            if ($board->labels()->count() >= self::MAXIMUM_PER_BOARD) {
                $validator->errors()->add('name', __('A board can have at most :count labels.', ['count' => self::MAXIMUM_PER_BOARD]));
            }
        }];
    }
}
