<?php

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['attachments.disk' => 'attachments']);
    Storage::fake('attachments');
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    $this->actingAs($this->user);

    $this->upload = function (UploadedFile $file): Attachment {
        $this->post(route('issues.attachments.store', $this->issue), ['files' => [$file]])->assertSessionHasNoErrors();

        return $this->issue->attachments()->latest('id')->firstOrFail();
    };
});

test('guests are redirected to login', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->create('a.pdf', 5));
    auth()->logout();

    $this->get(route('attachments.show', $attachment))->assertRedirect(route('login'));
    $this->get(route('attachments.thumbnail', $attachment))->assertRedirect(route('login'));
});

test('files are only served to people who can see the issue', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->image('a.png', 100, 100));
    $this->actingAs(User::factory()->create());

    $this->get(route('attachments.show', $attachment))->assertForbidden();
    $this->get(route('attachments.thumbnail', $attachment))->assertForbidden();

    $this->board->delete();
    $this->actingAs($this->user);
    $this->get(route('attachments.show', $attachment))->assertForbidden();
});

test('documents are downloaded, never shown or run', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->createWithContent('Quarterly report.pdf', '%PDF-1.4 fake'));

    $response = $this->get(route('attachments.show', $attachment))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')->toContain('Quarterly report.pdf')
        ->and($response->headers->get('Content-Type'))->toBe('application/octet-stream')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Content-Security-Policy'))->toContain('sandbox')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
    expect($response->streamedContent())->toBe('%PDF-1.4 fake');
});

test('pictures are shown in the browser, and their thumbnails too', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->image('photo.png', 800, 600));

    $original = $this->get(route('attachments.show', $attachment))->assertOk();
    $thumbnail = $this->get(route('attachments.thumbnail', $attachment))->assertOk();

    expect($original->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($original->headers->get('Content-Type'))->toBe('image/png')
        ->and($thumbnail->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($thumbnail->headers->get('Content-Type'))->toBe('image/webp')
        ->and($thumbnail->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

test('a text file is not shown even if it looks like a page', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->createWithContent('readme.txt', 'plain text'));

    $response = $this->get(route('attachments.show', $attachment))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')->and($response->headers->get('Content-Type'))->toBe('application/octet-stream');
});

test('there is no thumbnail for files that have none', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->create('a.pdf', 5));

    $this->get(route('attachments.thumbnail', $attachment))->assertNotFound();
});

test('deleted attachments and missing files are not found', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->create('a.pdf', 5));
    $missing = ($this->upload)(UploadedFile::fake()->create('b.pdf', 5));
    Storage::disk('attachments')->delete($missing->path);

    $this->get(route('attachments.show', $missing))->assertNotFound();

    $attachment->delete();
    $this->get(route('attachments.show', $attachment))->assertNotFound();
});

test('files stay readable from the disk they were saved to after the setting changes', function () {
    $attachment = ($this->upload)(UploadedFile::fake()->createWithContent('a.pdf', 'content'));
    config(['attachments.disk' => 'somewhere-else']);

    $this->get(route('attachments.show', $attachment))->assertOk();
});
