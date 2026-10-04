<?php

namespace App\Concerns;

use App\Enums\LabelColor;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait LabelValidationRules
{
    /**
     * Get the validation rules for a label. Names are unique within the
     * board, ignoring the label being edited.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function labelRules(Board $board, ?Label $label = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('labels', 'name')->where('board_id', $board->id)->ignore($label?->id)],
            'color' => ['required', Rule::enum(LabelColor::class)],
        ];
    }
}
