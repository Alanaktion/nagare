<?php

namespace App\Concerns;

use App\Models\Comment;
use Illuminate\Contracts\Validation\ValidationRule;

trait CommentValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function commentRules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.Comment::MAXIMUM_LENGTH],
        ];
    }
}
