<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

/**
 * @property int $id
 * @property int $issue_id
 * @property int|null $user_id
 * @property string $body
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['body'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * The most characters a comment can have.
     */
    public const int MAXIMUM_LENGTH = 10000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    /**
     * The comment's Markdown as safe HTML. Raw HTML in the comment is dropped,
     * unsafe link schemes such as `javascript:` are neutralized, and links to
     * other sites open in a new tab without passing on the referrer. Images
     * become links, so a comment can't make other people's browsers request
     * an address of its author's choosing.
     *
     * @return Attribute<string, never>
     */
    protected function bodyHtml(): Attribute
    {
        return Attribute::get(fn (): string => $this->imagesAsLinks(Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
            'external_link' => [
                'internal_hosts' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ], [new ExternalLinkExtension])));
    }

    /**
     * Replace the `<img>` tags in rendered Markdown with links to their sources.
     * The attribute values are already escaped by the Markdown renderer.
     */
    private function imagesAsLinks(string $html): string
    {
        return (string) preg_replace_callback('/<img\s[^>]*>/i', function (array $match): string {
            preg_match('/\ssrc="([^"]*)"/i', $match[0], $source);
            preg_match('/\salt="([^"]*)"/i', $match[0], $alt);

            if (! isset($source[1])) {
                return '';
            }

            $label = ($alt[1] ?? '') !== '' ? $alt[1] : 'image';

            return '<a href="'.$source[1].'" target="_blank" rel="nofollow noopener noreferrer">'.$label.'</a>';
        }, $html);
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
