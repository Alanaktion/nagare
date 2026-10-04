<?php

namespace App\Http\Requests\Comments;

use App\Concerns\CommentValidationRules;
use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCommentRequest extends FormRequest
{
    use CommentValidationRules;

    public function authorize(): bool
    {
        return Gate::allows('create', [Comment::class, $this->route('issue')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->commentRules();
    }
}
