<?php

namespace App\Notifications;

use App\Actions\Notifications\NotifyIssueWatchers;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * A new comment on a watched issue.
 */
class IssueCommented extends IssueNotification
{
    public int $commentId;

    public ?string $excerpt;

    public function __construct(Issue $issue, User $actor, Comment $comment)
    {
        parent::__construct($issue, $actor);

        $this->commentId = $comment->id;
        $text = trim((string) preg_replace('/\s+/', ' ', $comment->body));
        $this->excerpt = $text === '' ? __('Attached files.') : Str::limit($text, NotifyIssueWatchers::EXCERPT_LENGTH);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'commented',
            ...$this->common(),
            'comment_id' => $this->commentId,
            'excerpt' => $this->excerpt,
        ];
    }

    protected function subject(User $notifiable): string
    {
        return __(':actor commented on ":issue"', ['actor' => $this->actorName, 'issue' => $this->issueName]);
    }

    /**
     * @return list<string>
     */
    protected function lines(User $notifiable): array
    {
        return [
            __(':actor commented:', ['actor' => $this->actorName]),
            '> '.($this->excerpt ?? ''),
        ];
    }
}
