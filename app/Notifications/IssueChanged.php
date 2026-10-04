<?php

namespace App\Notifications;

use App\Enums\IssueActivityType;
use App\Models\Issue;
use App\Models\User;

/**
 * Changes someone made to a watched issue in one update: a status change,
 * a new assignee, or a new description.
 */
class IssueChanged extends IssueNotification
{
    /**
     * @param  list<array{type: string, from?: string|null, to?: string|null, to_id?: int|null, excerpt?: string|null}>  $changes
     */
    public function __construct(Issue $issue, User $actor, public array $changes)
    {
        parent::__construct($issue, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'changed',
            ...$this->common(),
            'changes' => array_map(
                fn (array $change) => $change + ['to_you' => $notifiable instanceof User && ($change['to_id'] ?? null) === $notifiable->id],
                $this->changes,
            ),
        ];
    }

    protected function subject(User $notifiable): string
    {
        $changes = $this->changes;

        if (count($changes) > 1) {
            return __(':actor updated ":issue"', ['actor' => $this->actorName, 'issue' => $this->issueName]);
        }

        return $this->actorName.' '.$this->describe($changes[0], $notifiable).': '.$this->issueName;
    }

    /**
     * @return list<string>
     */
    protected function lines(User $notifiable): array
    {
        return array_map(function (array $change) use ($notifiable): string {
            $line = $this->actorName.' '.$this->describe($change, $notifiable).'.';

            return $change['type'] === 'description' && ! empty($change['excerpt'])
                ? $line."\n\n> ".$change['excerpt']
                : $line;
        }, $this->changes);
    }

    /**
     * What one change did, without the actor: "moved it from To Do to Done".
     *
     * @param  array{type: string, from?: string|null, to?: string|null, to_id?: int|null, excerpt?: string|null}  $change
     */
    private function describe(array $change, User $notifiable): string
    {
        $from = $change['from'] ?? null;
        $to = $change['to'] ?? null;

        return match ($change['type']) {
            IssueActivityType::Moved->value => __('moved it from :from to :to', ['from' => $from ?? '?', 'to' => $to ?? '?']),
            IssueActivityType::Closed->value => __('closed it by moving it to :to', ['to' => $to ?? '?']),
            IssueActivityType::Reopened->value => __('reopened it by moving it to :to', ['to' => $to ?? '?']),
            IssueActivityType::Assigned->value => match (true) {
                ($change['to_id'] ?? null) === $notifiable->id => __('assigned it to you'),
                $to !== null => __('assigned it to :name', ['name' => $to]),
                default => __('unassigned :name', ['name' => $from ?? '?']),
            },
            default => ($change['excerpt'] ?? null) === null ? __('removed the description') : __('updated the description'),
        };
    }
}
