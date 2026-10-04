<?php

namespace App\Http\Requests\Sprints;

use App\Enums\SprintCycle;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Board $board */
        $board = $this->route('board');

        return $board->has_sprints && Gate::allows('update', $board);
    }

    /**
     * Fixed-cycle boards only need a date inside the sprint's period.
     * Custom-cycle boards also need the sprint's last day.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Board $board */
        $board = $this->route('board');

        return [
            'start_date' => ['required', 'date'],
            'end_date' => $board->sprintCycle() === SprintCycle::Custom
                ? ['required', 'date', 'after_or_equal:start_date']
                : ['nullable', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Board $board */
            $board = $this->route('board');
            $start = Carbon::parse($this->input('start_date'));
            $cycle = $board->sprintCycle();
            $slug = $cycle->slugFor($cycle->periodFor($start)[0] ?? $start);

            if ($board->sprints()->where('slug', $slug)->exists()) {
                $validator->errors()->add('start_date', __('A sprint already exists for that period (:slug).', ['slug' => $slug]));
            }
        }];
    }
}
