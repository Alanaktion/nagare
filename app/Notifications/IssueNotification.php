<?php

namespace App\Notifications;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Something that happened to an issue someone is watching. Notifications are
 * sent as soon as the change is made, to the database for the notifications
 * page, to the user's private channel for live updates, and by email unless
 * they turned that off. Everything is copied from the models when the
 * notification is created, so it still reads right if they change or go away
 * before a queued notification is processed.
 */
abstract class IssueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $issueId;

    public string $issueName;

    public int $boardId;

    public string $boardName;

    public int $actorId;

    public string $actorName;

    public function __construct(Issue $issue, User $actor)
    {
        $this->issueId = $issue->id;
        $this->issueName = $issue->name;
        $this->boardId = $issue->board_id;
        $this->boardName = $issue->board->name ?? '';
        $this->actorId = $actor->id;
        $this->actorName = $actor->name;

        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable instanceof User && $notifiable->email_notifications) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * The subject of the email, saying who did what.
     */
    abstract protected function subject(User $notifiable): string;

    /**
     * The lines of the email, one for each thing that happened.
     *
     * @return list<string>
     */
    abstract protected function lines(User $notifiable): array;

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(object $notifiable): array;

    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("[{$this->boardName}] ".$this->subject($notifiable))
            ->greeting(__('Hi :name,', ['name' => $notifiable->name]));

        foreach ($this->lines($notifiable) as $line) {
            $message->line($line);
        }

        return $message
            ->action(__('View issue'), route('issues.show', $this->issueId))
            ->line(__('You are getting this email because you are watching ":issue". You can stop email notifications in your settings: :url', [
                'issue' => $this->issueName,
                'url' => route('notifications-settings.edit'),
            ]));
    }

    /**
     * The details every kind of notification carries.
     *
     * @return array<string, mixed>
     */
    protected function common(): array
    {
        return [
            'issue_id' => $this->issueId,
            'issue_name' => $this->issueName,
            'board_id' => $this->boardId,
            'board_name' => $this->boardName,
            'actor' => ['id' => $this->actorId, 'name' => $this->actorName],
        ];
    }
}
