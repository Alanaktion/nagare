<?php

use App\Actions\Users\DeleteUser;
use App\Enums\BoardRole;
use App\Enums\IssueActivityType;
use App\Events\IssueTimelineChanged;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['attachments.disk' => 'attachments']);
    Storage::fake('attachments');
    $this->uploader = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->uploader)->create();
    $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    $this->actingAs($this->uploader);
    $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->image('photo.png', 400, 300)]]);
    $this->attachment = $this->issue->attachments()->sole();
});

/**
 * Add a user to the board.
 */
function memberOf(Board $board, BoardRole $role = BoardRole::Member): User
{
    $user = User::factory()->create();
    $board->users()->attach($user, ['role' => $role->value]);

    return $user;
}

describe('deleting an attachment', function () {
    test('guests are redirected to login', function () {
        auth()->logout();

        $this->delete(route('attachments.destroy', $this->attachment))->assertRedirect(route('login'));
    });

    test('hides it at once, keeps the files for now, and notes it in the timeline', function () {
        $this->delete(route('attachments.destroy', $this->attachment))->assertSessionHasNoErrors();

        expect(Attachment::find($this->attachment->id))->toBeNull()
            ->and(Attachment::withTrashed()->find($this->attachment->id)->trashed())->toBeTrue()
            ->and($this->issue->activities()->latest('id')->first())->type->toBe(IssueActivityType::AttachmentRemoved)->data->toBe(['name' => 'photo.png']);
        Storage::disk('attachments')->assertExists($this->attachment->path);
        Storage::disk('attachments')->assertExists($this->attachment->thumbnail_path);
    });

    test('the uploader and board admins can delete, other members and outsiders cannot', function () {
        $this->actingAs(memberOf($this->board))->delete(route('attachments.destroy', $this->attachment))->assertForbidden();
        $this->actingAs(User::factory()->create())->delete(route('attachments.destroy', $this->attachment))->assertForbidden();
        expect(Attachment::count())->toBe(1);

        $this->actingAs(memberOf($this->board, BoardRole::Admin))->delete(route('attachments.destroy', $this->attachment))->assertSessionHasNoErrors();
        expect(Attachment::count())->toBe(0);
    });

    test('uploaders who left the board can no longer delete', function () {
        $this->board->users()->detach($this->uploader);

        $this->delete(route('attachments.destroy', $this->attachment))->assertForbidden();
    });

    test('a comment\'s file is removed quietly, without a timeline entry', function () {
        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi', 'files' => [UploadedFile::fake()->create('log.txt', 1)]]);
        $inComment = $this->issue->attachments()->whereNotNull('comment_id')->sole();
        $activities = $this->issue->activities()->count();
        Event::fake([IssueTimelineChanged::class]);

        $this->delete(route('attachments.destroy', $inComment))->assertSessionHasNoErrors();

        expect($this->issue->activities()->count())->toBe($activities);
        Event::assertDispatched(IssueTimelineChanged::class);
    });
});

test('deleting a comment deletes its files with it', function () {
    $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi', 'files' => [UploadedFile::fake()->create('log.txt', 1)]]);
    $comment = Comment::sole();
    $inComment = $this->issue->attachments()->where('comment_id', $comment->id)->sole();

    $this->delete(route('comments.destroy', $comment))->assertSessionHasNoErrors();

    expect(Attachment::find($inComment->id))->toBeNull()->and(Attachment::withTrashed()->find($inComment->id))->not->toBeNull()
        ->and(Attachment::find($this->attachment->id))->not->toBeNull();
});

test('deleting an issue hides its files, which are removed later', function () {
    $this->delete(route('issues.destroy', $this->issue))->assertSessionHasNoErrors();

    expect(Attachment::count())->toBe(0)->and(Attachment::withTrashed()->count())->toBe(1);
    Storage::disk('attachments')->assertExists($this->attachment->path);
});

test('deleting an issue for good removes its files straight away', function () {
    $this->issue->forceDelete();

    Storage::disk('attachments')->assertMissing($this->attachment->path);
    Storage::disk('attachments')->assertMissing($this->attachment->thumbnail_path);
    expect(Attachment::withTrashed()->count())->toBe(0);
});

test('attachments outlive their uploader\'s account', function () {
    app(DeleteUser::class)->handle($this->uploader);

    expect($this->attachment->fresh())->not->toBeNull()->user_id->toBeNull();
});

describe('pruning', function () {
    test('removes the files and records of attachments deleted long enough ago', function () {
        $this->travelTo('2026-10-01 12:00:00');
        $old = Attachment::factory()->for($this->issue)->create(['path' => 'attachments/old.pdf', 'thumbnail_path' => 'attachments/old-thumbnail.webp']);
        $recent = Attachment::factory()->for($this->issue)->create(['path' => 'attachments/recent.pdf']);
        $live = Attachment::factory()->for($this->issue)->create(['path' => 'attachments/live.pdf']);
        foreach ([$old->path, $old->thumbnail_path, $recent->path, $live->path] as $path) {
            Storage::disk('attachments')->put($path, 'x');
        }
        $old->delete();
        $this->travel(10)->days();
        $recent->delete();
        $this->travel(21)->days();

        $this->artisan('attachments:prune')->expectsOutputToContain('Removed 1 deleted attachment(s).')->assertSuccessful();

        Storage::disk('attachments')->assertMissing($old->path);
        Storage::disk('attachments')->assertMissing($old->thumbnail_path);
        Storage::disk('attachments')->assertExists($recent->path);
        Storage::disk('attachments')->assertExists($live->path);
        expect(Attachment::withTrashed()->find($old->id))->toBeNull()
            ->and(Attachment::withTrashed()->find($recent->id))->not->toBeNull()
            ->and(Attachment::find($live->id))->not->toBeNull();
    });

    test('can be told how many days to keep files', function () {
        $deleted = Attachment::factory()->for($this->issue)->create(['path' => 'attachments/x.pdf']);
        Storage::disk('attachments')->put($deleted->path, 'x');
        $deleted->delete();
        $this->travel(2)->days();

        $this->artisan('attachments:prune', ['--days' => 5])->expectsOutputToContain('Removed 0')->assertSuccessful();
        $this->artisan('attachments:prune', ['--days' => 1])->expectsOutputToContain('Removed 1')->assertSuccessful();

        Storage::disk('attachments')->assertMissing($deleted->path);
    });

    test('runs every day', function () {
        $commands = collect(app(Schedule::class)->events())->pluck('command');

        expect($commands->contains(fn (string $command) => str_contains($command, 'attachments:prune')))->toBeTrue();
    });
});
