<?php

namespace App\Actions\Notifications;

use App\Actions\Issues\WatchIssue;
use App\Enums\IssueActivityType;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use App\Notifications\IssueChanged;
use App\Notifications\IssueCommented;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

class NotifyIssueWatchers
{
    /**
     * The longest excerpt of a description or comment put in a notification.
     */
    public const int EXCERPT_LENGTH = 200;

    public function __construct(private WatchIssue $watchIssue) {}

    /**
     * Notify the watchers of a new issue that it was assigned to someone.
     */
    public function forCreate(Issue $issue, User $actor): void
    {
        if ($issue->assigned_id === null) {
            return;
        }

        $assignee = User::query()->find($issue->assigned_id);

        if ($assignee === null) {
            return;
        }

        $this->watchIssue->handle($issue, $assignee);

        $this->handle($issue, $actor, new IssueChanged($issue, $actor, [
            ['type' => IssueActivityType::Assigned->value, 'from' => null, 'to' => $assignee->name, 'to_id' => $assignee->id],
        ]));
    }

    /**
     * Notify watchers of the changes that matter to them: a status change
     * (including closing and reopening), an assignment change, and a new
     * description. Other changes, such as renames and labels, don't notify.
     * Whoever becomes the assignee starts watching and hears about it first.
     * One notification covers everything a single update changed.
     *
     * @param  list<array{0: IssueActivityType, 1: array<string, mixed>}>  $entries  What the update recorded in the timeline.
     */
    public function forUpdate(Issue $issue, array $entries, bool $descriptionChanged, ?User $actor): void
    {
        $changes = [];

        foreach ($entries as [$type, $data]) {
            if (in_array($type, [IssueActivityType::Moved, IssueActivityType::Closed, IssueActivityType::Reopened], true)) {
                $changes[] = ['type' => $type->value, 'from' => $data['from'] ?? null, 'to' => $data['to'] ?? null];
            }

            if ($type === IssueActivityType::Assigned) {
                $changes[] = ['type' => $type->value, 'from' => $data['from'] ?? null, 'to' => $data['to'] ?? null, 'to_id' => $issue->assigned_id];

                $assignee = $issue->assigned_id === null ? null : User::query()->find($issue->assigned_id);

                if ($assignee !== null) {
                    $this->watchIssue->handle($issue, $assignee);
                }
            }
        }

        if ($descriptionChanged) {
            $changes[] = ['type' => 'description', 'excerpt' => $this->excerpt($issue->description)];
        }

        if ($changes !== [] && $actor !== null) {
            $this->handle($issue, $actor, new IssueChanged($issue, $actor, $changes));
        }
    }

    /**
     * Notify watchers of a new comment.
     */
    public function forComment(Issue $issue, Comment $comment, User $author): void
    {
        $this->handle($issue, $author, new IssueCommented($issue, $author, $comment));
    }

    /**
     * Send a notification to everyone watching the issue except whoever made
     * the change. Watchers who have since left the board are skipped.
     */
    public function handle(Issue $issue, User $actor, Notification $notification): void
    {
        $board = $issue->board;

        if ($board === null) {
            return;
        }

        $recipients = $issue->watchers()
            ->whereIn('users.id', $board->users()->select('users.id'))
            ->whereKeyNot($actor->id)
            ->get();

        NotificationFacade::send($recipients, $notification);
    }

    /**
     * The start of a text on one line, or null when there is none.
     */
    public function excerpt(?string $text): ?string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', (string) $text));

        return $text === '' ? null : Str::limit($text, self::EXCERPT_LENGTH);
    }
}
