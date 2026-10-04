<?php

namespace App\Actions\Issues;

use App\Enums\BoardRole;
use App\Http\Resources\CommentResource;
use App\Http\Resources\IssueActivityResource;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildIssueTimeline
{
    /**
     * The most entries a timeline shows, newest kept.
     */
    public const int LIMIT = 200;

    /**
     * An issue's comments and recorded changes, oldest first. Each comment
     * says whether the viewer may edit or delete it.
     *
     * @return list<array<string, mixed>>
     */
    public function handle(Issue $issue, User $viewer): array
    {
        $isAdmin = $issue->board?->roleFor($viewer) === BoardRole::Admin;

        $comments = $issue->comments()->with(['user', 'attachments.user'])->latest()->latest('id')->limit(self::LIMIT)->get();
        $activities = $issue->activities()->with('user')->latest('created_at')->latest('id')->limit(self::LIMIT)->get();

        $entries = [];

        foreach ($comments as $comment) {
            $isAuthor = $comment->user_id === $viewer->id;
            $comment->setAttribute('can_update', $isAuthor);
            $comment->setAttribute('can_delete', $isAuthor || $isAdmin);

            foreach ($comment->attachments as $attachment) {
                $attachment->setAttribute('can_delete', $attachment->user_id === $viewer->id || $isAdmin);
            }

            $entries[] = [
                'sort' => [$comment->created_at?->getTimestamp() ?? 0, 1, $comment->id],
                'entry' => ['kind' => 'comment', ...$this->plain(new CommentResource($comment))],
            ];
        }

        foreach ($activities as $activity) {
            $entries[] = [
                'sort' => [$activity->created_at->getTimestamp(), 0, $activity->id],
                'entry' => ['kind' => 'activity', ...$this->plain(new IssueActivityResource($activity))],
            ];
        }

        usort($entries, fn (array $a, array $b) => $a['sort'] <=> $b['sort']);

        return array_map(fn (array $item) => $item['entry'], array_slice($entries, -self::LIMIT));
    }

    /**
     * A resource as plain data. Entries are mixed into one list, so nested
     * resources must be resolved here instead of wrapped in `data` later.
     *
     * @return array<string, mixed>
     */
    private function plain(JsonResource $resource): array
    {
        /** @var array<string, mixed> */
        return json_decode((string) json_encode($resource), true);
    }
}
