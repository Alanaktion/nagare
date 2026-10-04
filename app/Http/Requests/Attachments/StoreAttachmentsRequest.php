<?php

namespace App\Http\Requests\Attachments;

use App\Concerns\AttachmentValidationRules;
use App\Models\Attachment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreAttachmentsRequest extends FormRequest
{
    use AttachmentValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('create', [Attachment::class, $this->route('issue')]);
    }

    /**
     * @return array<string, array<int, ValidationRule|\Closure|string>>
     */
    public function rules(): array
    {
        $rules = $this->attachmentRules();
        $rules['files'] = ['required', ...$rules['files']];

        return $rules;
    }
}
