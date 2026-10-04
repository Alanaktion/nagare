<?php

namespace App\Http\Requests\Comments;

use App\Concerns\CommentValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCommentRequest extends FormRequest
{
    use CommentValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('comment'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->commentRules();
    }
}
