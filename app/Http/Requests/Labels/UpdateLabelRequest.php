<?php

namespace App\Http\Requests\Labels;

use App\Concerns\LabelValidationRules;
use App\Models\Label;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateLabelRequest extends FormRequest
{
    use LabelValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('label'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Label $label */
        $label = $this->route('label');

        return $this->labelRules($label->board ?? abort(404), $label);
    }
}
