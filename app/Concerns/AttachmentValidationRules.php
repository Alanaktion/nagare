<?php

namespace App\Concerns;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

trait AttachmentValidationRules
{
    /**
     * Content types that are refused whatever the file is named, because a
     * browser could run or render them as a page.
     *
     * @var list<string>
     */
    private const array BLOCKED_MIME_TYPES = [
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',
        'text/xml',
        'application/xml',
        'application/javascript',
        'text/javascript',
        'application/x-httpd-php',
        'text/x-php',
        'application/x-sh',
        'text/x-shellscript',
        'application/x-msdownload',
        'application/x-dosexec',
        'application/x-executable',
    ];

    /**
     * The rules for a list of uploaded files, from the `attachments`
     * configuration: how many, how large and which types.
     *
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function attachmentRules(string $field = 'files'): array
    {
        return [
            $field => ['array', 'max:'.(int) config('attachments.max_files')],
            "{$field}.*" => [
                'file',
                'max:'.(int) config('attachments.max_size_kb'),
                'extensions:'.implode(',', (array) config('attachments.extensions')),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value instanceof UploadedFile && in_array($value->getMimeType(), self::BLOCKED_MIME_TYPES, true)) {
                        $fail(__('The :attribute must not be a web page, script or program.'));
                    }
                },
            ],
        ];
    }
}
