<?php

use App\Enums\IssueActivityType;
use App\Events\IssueTimelineChanged;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['attachments.disk' => 'attachments']);
    Storage::fake('attachments');
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    $this->actingAs($this->user);
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->create('a.pdf', 10)]])->assertRedirect(route('login'));
});

test('non-members cannot attach files', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->create('a.pdf', 10)]])->assertForbidden();

    expect(Attachment::count())->toBe(0);
});

test('members can attach several files at once', function () {
    $this->post(route('issues.attachments.store', $this->issue), ['files' => [
        UploadedFile::fake()->create('Spec Sheet.PDF', 120, 'application/pdf'),
        UploadedFile::fake()->createWithContent('notes.txt', 'hello'),
    ]])->assertSessionHasNoErrors();

    $attachments = $this->issue->attachments()->orderBy('id')->get();

    expect($attachments)->toHaveCount(2)
        ->and($attachments[0])->name->toBe('Spec Sheet.PDF')->mime_type->toBe('application/pdf')->user_id->toBe($this->user->id)->comment_id->toBeNull()->disk->toBe('attachments')
        ->and($attachments[1])->name->toBe('notes.txt')->size->toBe(5);

    foreach ($attachments as $attachment) {
        expect($attachment->path)->toStartWith("attachments/{$this->issue->id}/")->not->toContain('notes')->not->toContain('Spec');
        Storage::disk('attachments')->assertExists($attachment->path);
    }

    expect($attachments[0]->path)->toEndWith('.pdf');
});

test('attaching is noted in the timeline and announced once', function () {
    Event::fake([IssueTimelineChanged::class]);

    $this->post(route('issues.attachments.store', $this->issue), ['files' => [
        UploadedFile::fake()->create('a.pdf', 10),
        UploadedFile::fake()->create('b.pdf', 10),
    ]]);

    $activity = $this->issue->activities()->sole();
    expect($activity->type)->toBe(IssueActivityType::Attached)->and($activity->data)->toBe(['names' => ['a.pdf', 'b.pdf']])->and($activity->user_id)->toBe($this->user->id);
    Event::assertDispatchedTimes(IssueTimelineChanged::class, 1);
});

describe('pictures', function () {
    test('get a small webp thumbnail', function () {
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->image('photo.png', 1000, 500)]]);

        $attachment = $this->issue->attachments()->sole();
        Storage::disk('attachments')->assertExists($attachment->thumbnail_path);
        [$width, $height] = getimagesizefromstring(Storage::disk('attachments')->get($attachment->thumbnail_path));

        expect($attachment->mime_type)->toBe('image/png')
            ->and($attachment->thumbnail_path)->toEndWith('-thumbnail.webp')
            ->and($width)->toBe(320)->and($height)->toBe(160);
    });

    test('too large to decode safely are stored without one', function () {
        config(['attachments.max_thumbnail_pixels' => 100]);

        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->image('big.jpg', 50, 50)]])->assertSessionHasNoErrors();

        expect($this->issue->attachments()->sole()->thumbnail_path)->toBeNull();
    });

    test('are the only files that get one', function () {
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]]);

        expect($this->issue->attachments()->sole()->thumbnail_path)->toBeNull();
    });
});

describe('validation', function () {
    test('files are required', function (array $input) {
        $this->post(route('issues.attachments.store', $this->issue), $input)->assertSessionHasErrors('files');
    })->with([
        'missing' => [['unrelated' => 'x']],
        'empty' => [['files' => []]],
    ]);

    test('there is a limit on how many files go in one upload', function () {
        $files = array_map(fn (int $number) => UploadedFile::fake()->create("{$number}.pdf", 1), range(1, config('attachments.max_files') + 1));

        $this->post(route('issues.attachments.store', $this->issue), ['files' => $files])->assertSessionHasErrors('files');

        expect(Attachment::count())->toBe(0);
    });

    test('each file has a size limit', function () {
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->create('big.pdf', config('attachments.max_size_kb') + 1)]])
            ->assertSessionHasErrors('files.0');
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->create('fits.pdf', config('attachments.max_size_kb'))]])
            ->assertSessionHasNoErrors();
    });

    test('only allowed types are accepted', function (string $name) {
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->createWithContent($name, 'content')]])
            ->assertSessionHasErrors('files.0');

        expect(Attachment::count())->toBe(0);
    })->with(['program' => ['run.exe'], 'page' => ['page.html'], 'svg' => ['logo.svg'], 'script' => ['shell.php'], 'no extension' => ['README'], 'double extension' => ['archive.pdf.exe']]);

    test('web pages and scripts are refused even when named like something harmless', function (string $content) {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);
        $file = new UploadedFile($path, 'notes.txt', null, null, true);

        $this->post(route('issues.attachments.store', $this->issue), ['files' => [$file]])->assertSessionHasErrors('files.0');

        expect(Attachment::count())->toBe(0);
    })->with([
        'html' => ['<!DOCTYPE html><html><body><script>alert(1)</script></body></html>'],
        'svg' => ['<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'],
    ]);

    test('one bad file stops the whole upload', function () {
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->create('ok.pdf', 1), UploadedFile::fake()->create('bad.exe', 1)]])
            ->assertSessionHasErrors('files.1');

        expect(Attachment::count())->toBe(0);
    });
});

test('there is no limit on how many files an issue or a board can have', function () {
    foreach (range(1, 3) as $round) {
        $files = array_map(fn (int $number) => UploadedFile::fake()->create("round{$round}-{$number}.pdf", 1), range(1, config('attachments.max_files')));
        $this->post(route('issues.attachments.store', $this->issue), ['files' => $files])->assertSessionHasNoErrors();
    }

    expect($this->issue->attachments()->count())->toBe(config('attachments.max_files') * 3);
});

describe('on comments', function () {
    test('a comment can carry files, which belong to the issue and the comment', function () {
        $this->post(route('issues.comments.store', $this->issue), [
            'body' => 'See the attached log',
            'files' => [UploadedFile::fake()->createWithContent('debug.log', 'trace')],
        ])->assertSessionHasNoErrors();

        $comment = $this->issue->comments()->sole();
        $attachment = $this->issue->attachments()->sole();

        expect($attachment->comment_id)->toBe($comment->id)->and($attachment->user_id)->toBe($this->user->id)
            ->and($this->issue->activities()->count())->toBe(0);
        Storage::disk('attachments')->assertExists($attachment->path);
    });

    test('a comment can be only files', function () {
        $this->post(route('issues.comments.store', $this->issue), ['files' => [UploadedFile::fake()->create('a.pdf', 1)]])->assertSessionHasNoErrors();

        expect($this->issue->comments()->sole()->body)->toBe('');
    });

    test('a comment still needs text or files, and bad files stop it', function () {
        $this->post(route('issues.comments.store', $this->issue), ['body' => ''])->assertSessionHasErrors('body');
        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi', 'files' => [UploadedFile::fake()->create('bad.exe', 1)]])->assertSessionHasErrors('files.0');

        expect($this->issue->comments()->count())->toBe(0);
    });

    test('non-members cannot attach files through a comment', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi', 'files' => [UploadedFile::fake()->create('a.pdf', 1)]])->assertForbidden();

        expect(Attachment::count())->toBe(0);
    });

    test('the timeline shows a comment\'s files while the issue lists the others', function () {
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [UploadedFile::fake()->create('on-issue.pdf', 5)]]);
        $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi', 'files' => [UploadedFile::fake()->image('in-comment.png', 200, 100)]]);
        $comment = $this->issue->comments()->sole();
        $onIssue = $this->issue->attachments()->whereNull('comment_id')->sole();

        $this->get(route('issues.show', $this->issue))->assertInertia(fn (Assert $page) => $page
            ->has('attachments.data', 1)
            ->where('attachments.data.0.name', 'on-issue.pdf')
            ->where('attachments.data.0.url', route('attachments.show', $onIssue))
            ->where('attachments.data.0.thumbnail_url', null)
            ->where('attachments.data.0.is_image', false)
            ->where('attachments.data.0.can_delete', true)
            ->where('attachments.data.0.user.id', $this->user->id)
            ->loadDeferredProps(fn (Assert $deferred) => $deferred
                ->where('timeline', function ($timeline) use ($comment) {
                    $entry = collect($timeline)->first(fn (array $entry) => $entry['kind'] === 'comment' && $entry['id'] === $comment->id);

                    return $entry['attachments'][0]['name'] === 'in-comment.png'
                        && $entry['attachments'][0]['is_image'] === true
                        && $entry['attachments'][0]['thumbnail_url'] !== null;
                })));
    });
});
