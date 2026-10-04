<?php

use App\Actions\Users\DeleteUser;
use App\Http\Controllers\NotificationController;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actor = User::factory()->create(['name' => 'Ada']);
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->actor)->create();
    $this->board->users()->attach($this->user, ['role' => 'member']);
    $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => 'Fix login']);
    $this->other = Issue::factory()->inStatus($this->board->statuses[0])->create(['name' => 'Other issue']);
    $this->issue->watchers()->attach([$this->actor->id, $this->user->id]);
    $this->other->watchers()->attach([$this->actor->id, $this->user->id]);
});

/**
 * Make the actor comment on an issue, which notifies the user.
 */
function commentAsActor(Issue $issue, string $body = 'Hello'): void
{
    test()->actingAs(test()->actor)->post(route('issues.comments.store', $issue), ['body' => $body]);
}

test('guests are redirected to login', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
    $this->put(route('notifications.update'))->assertRedirect(route('login'));
});

test('changes are delivered to the database right away', function () {
    $this->actingAs($this->actor)->put(route('issues.update', $this->issue), ['status_id' => $this->board->statuses[1]->id]);
    $this->travel(1)->minute();
    commentAsActor($this->issue, 'Looks good');

    $notifications = $this->user->notifications()->reorder('created_at')->get();

    expect($notifications)->toHaveCount(2)
        ->and($notifications[0]->data)->toMatchArray(['kind' => 'changed', 'issue_id' => $this->issue->id, 'issue_name' => 'Fix login', 'actor' => ['id' => $this->actor->id, 'name' => 'Ada']])
        ->and($notifications[0]->data['changes'][0])->toMatchArray(['type' => 'moved', 'from' => 'To Do', 'to' => 'In Progress'])
        ->and($notifications[1]->data)->toMatchArray(['kind' => 'commented', 'excerpt' => 'Looks good'])
        ->and($notifications->every(fn ($notification) => $notification->read_at === null))->toBeTrue()
        ->and($this->actor->notifications()->count())->toBe(0);
});

test('email is sent unless the user turned it off', function () {
    $sentEmails = fn () => Mail::mailer()->getSymfonyTransport()->messages()->map(fn ($message) => $message->getOriginalMessage()->getSubject())->all();

    $this->user->forceFill(['email_notifications' => false])->save();
    commentAsActor($this->issue);

    expect($this->user->notifications()->count())->toBe(1)->and($sentEmails())->toBe([]);

    $this->user->forceFill(['email_notifications' => true])->save();
    commentAsActor($this->issue, 'Second');

    expect($this->user->notifications()->count())->toBe(2)->and($sentEmails())->toHaveCount(1)->and($sentEmails()[0])->toContain('Ada commented on "Fix login"');
});

test('the notifications page lists the user\'s own notifications, newest first', function () {
    commentAsActor($this->issue, 'First');
    $this->travel(1)->minute();
    commentAsActor($this->other, 'Second');
    $this->actingAs($this->actor);
    $this->get(route('notifications.index'))->assertInertia(fn (Assert $page) => $page->has('notifications.data', 0));

    $this->actingAs($this->user)->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/Index')
            ->has('notifications.data', 2)
            ->where('notifications.data.0.data.excerpt', 'Second')
            ->where('notifications.data.1.data.excerpt', 'First')
            ->where('notifications.data.0.read_at', null));
});

test('the notifications page is paginated', function () {
    foreach (range(1, NotificationController::PER_PAGE + 3) as $number) {
        commentAsActor($this->issue, "Comment {$number}");
    }

    $this->actingAs($this->user)->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', NotificationController::PER_PAGE)
            ->where('notifications.meta.total', NotificationController::PER_PAGE + 3));
});

test('the unread count is shared with every page', function () {
    commentAsActor($this->issue);
    commentAsActor($this->other);

    $this->actingAs($this->user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('unreadNotifications', 2));
    $this->actingAs($this->actor)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('unreadNotifications', 0));
});

test('opening an issue reads the notifications about it and no others', function () {
    commentAsActor($this->issue);
    commentAsActor($this->other);

    $this->actingAs($this->user)->get(route('issues.show', $this->issue))->assertOk();

    $unread = $this->user->unreadNotifications()->get();
    expect($unread)->toHaveCount(1)->and($unread[0]->data['issue_id'])->toBe($this->other->id);
});

test('all notifications can be marked as read', function () {
    commentAsActor($this->issue);
    commentAsActor($this->other);
    commentAsActor($this->other);
    $untouched = $this->actor->notifications()->create([
        'id' => (string) Str::uuid(), 'type' => 'x', 'data' => ['issue_id' => 1], 'read_at' => null,
    ]);

    $this->actingAs($this->user)->put(route('notifications.update'))->assertSessionHasNoErrors();

    expect($this->user->unreadNotifications()->count())->toBe(0)
        ->and($this->user->notifications()->count())->toBe(3)
        ->and($untouched->fresh()->read_at)->toBeNull();
});

test('deleting an account deletes its notifications', function () {
    commentAsActor($this->issue);

    app(DeleteUser::class)->handle($this->user);

    expect(DB::table('notifications')->count())->toBe(0);
});

describe('settings', function () {
    test('the page shows the current preference', function () {
        $this->actingAs($this->user)->get(route('notifications-settings.edit'))
            ->assertInertia(fn (Assert $page) => $page->component('settings/Notifications')->where('emailNotifications', true));
    });

    test('email can be turned off and on', function () {
        $this->actingAs($this->user)->put(route('notifications-settings.update'), ['email_notifications' => false])->assertSessionHasNoErrors();
        expect($this->user->fresh()->email_notifications)->toBeFalse();

        $this->put(route('notifications-settings.update'), ['email_notifications' => true]);
        expect($this->user->fresh()->email_notifications)->toBeTrue();
    });

    test('the preference is required and guests cannot change it', function () {
        $this->actingAs($this->user)->put(route('notifications-settings.update'), [])->assertSessionHasErrors('email_notifications');

        auth()->logout();
        $this->put(route('notifications-settings.update'), ['email_notifications' => false])->assertRedirect(route('login'));
    });
});
