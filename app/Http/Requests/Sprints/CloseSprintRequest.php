<?php

namespace App\Http\Requests\Sprints;

use App\Models\Sprint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CloseSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('board'));
    }

    /**
     * Unfinished issues may move to another open sprint on the same board.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Sprint $sprint */
        $sprint = $this->route('sprint');

        return [
            'move_to' => ['nullable', 'integer', Rule::exists('sprints', 'id')
                ->where('board_id', $sprint->board_id)
                ->whereNull('closed_at')
                ->whereNot('id', $sprint->id)],
        ];
    }
}
