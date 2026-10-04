<?php

namespace App\Http\Requests\Boards;

use App\Concerns\BoardValidationRules;
use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateBoardRequest extends FormRequest
{
    use BoardValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('board'));
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

        return [
            ...$this->boardRules($board),
            'status_moves' => ['nullable', 'array'],
            'status_moves.*' => ['integer'],
        ];
    }

    /**
     * Every removed status that still has issues needs a remaining status
     * to move those issues to.
     *
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
            $keptIds = collect($this->array('statuses'))->pluck('id')->filter()->map(intval(...))->all();

            $board->statuses()->whereNotIn('id', $keptIds)->withCount('issues')->get()
                ->filter(fn ($status) => $status->issues_count > 0)
                ->each(function ($status) use ($validator, $keptIds): void {
                    if (! in_array((int) $this->input("status_moves.{$status->id}"), $keptIds, true)) {
                        $validator->errors()->add(
                            "status_moves.{$status->id}",
                            __('Choose a status to move the :count issues in ":name" to.', ['count' => $status->issues_count, 'name' => $status->name]),
                        );
                    }
                });
        }];
    }
}
