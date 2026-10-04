<?php

namespace App\Http\Requests\Comments;

use App\Concerns\AttachmentValidationRules;
use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCommentRequest extends FormRequest
{
    use AttachmentValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('create', [Comment::class, $this->route('issue')]);
    }

    /**
     * A comment needs text, files, or both.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->attachmentRules(),
            'body' => ['nullable', 'required_without:files', 'string', 'max:'.Comment::MAXIMUM_LENGTH],
        ];
    }
}
