<?php

use App\Enums\BoardRole;
use App\Events\IssueTimelineChanged;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->author)->create();
    $this->issue = Issue::factory()->inStatus($this->board->statuses[0])->create();
    $this->actingAs($this->author);
});

/**
 * Add a user to the board.
 */
function boardMember(Board $board, BoardRole $role = BoardRole::Member): User
{
    $user = User::factory()->create();
    $board->users()->attach($user, ['role' => $role->value]);

    return $user;
}

test('guests are redirected to login', function () {
    $comment = Comment::factory()->for($this->issue)->create();
    auth()->logout();

    $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi'])->assertRedirect(route('login'));
    $this->put(route('comments.update', $comment), ['body' => 'Hi'])->assertRedirect(route('login'));
    $this->delete(route('comments.destroy', $comment))->assertRedirect(route('login'));
});

test('members can comment', function () {
    Event::fake([IssueTimelineChanged::class]);

    $this->post(route('issues.comments.store', $this->issue), ['body' => 'Looks **good**'])->assertSessionHasNoErrors();

    $comment = $this->issue->comments()->sole();
    expect($comment)->body->toBe('Looks **good**')->user_id->toBe($this->author->id)->edited_at->toBeNull();
    Event::assertDispatched(IssueTimelineChanged::class, fn (IssueTimelineChanged $event) => $event->issueId === $this->issue->id && $event->boardId === $this->board->id);
});

test('non-members cannot comment, edit or delete', function () {
    $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id]);
    $this->actingAs(User::factory()->create());

    $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi'])->assertForbidden();
    $this->put(route('comments.update', $comment), ['body' => 'Hi'])->assertForbidden();
    $this->delete(route('comments.destroy', $comment))->assertForbidden();

    expect($this->issue->comments()->count())->toBe(1)->and($comment->fresh()->body)->not->toBe('Hi');
});

test('comments on a deleted board\'s issues are not allowed', function () {
    $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id]);
    $this->board->delete();

    $this->post(route('issues.comments.store', $this->issue), ['body' => 'Hi'])->assertForbidden();
    $this->put(route('comments.update', $comment), ['body' => 'Hi'])->assertForbidden();
    $this->delete(route('comments.destroy', $comment))->assertForbidden();
});

test('comment text is validated', function (array $input) {
    $this->post(route('issues.comments.store', $this->issue), $input)->assertSessionHasErrors('body');

    expect($this->issue->comments()->count())->toBe(0);
})->with([
    'missing' => [['unrelated' => 'x']],
    'empty' => [['body' => '']],
    'only spaces' => [['body' => '   ']],
    'too long' => [['body' => str_repeat('a', Comment::MAXIMUM_LENGTH + 1)]],
]);

describe('editing', function () {
    test('authors can edit their comments, which marks them as edited', function () {
        $this->travelTo('2026-10-05 12:00:00');
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id, 'body' => 'Before']);

        $this->put(route('comments.update', $comment), ['body' => 'After'])->assertSessionHasNoErrors();

        expect($comment->fresh())->body->toBe('After')->and($comment->fresh()->edited_at->toDateTimeString())->toBe('2026-10-05 12:00:00');
    });

    test('saving the same text is not an edit and announces nothing', function () {
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id, 'body' => 'Same']);
        Event::fake([IssueTimelineChanged::class]);

        $this->put(route('comments.update', $comment), ['body' => 'Same'])->assertSessionHasNoErrors();

        expect($comment->fresh()->edited_at)->toBeNull();
        Event::assertNotDispatched(IssueTimelineChanged::class);
    });

    test('only the author can edit, not even an admin', function () {
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id, 'body' => 'Mine']);

        foreach ([boardMember($this->board), boardMember($this->board, BoardRole::Admin)] as $other) {
            $this->actingAs($other)->put(route('comments.update', $comment), ['body' => 'Changed'])->assertForbidden();
        }

        expect($comment->fresh()->body)->toBe('Mine');
    });

    test('authors who left the board can no longer edit', function () {
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id, 'body' => 'Mine']);
        $this->board->users()->detach($this->author);

        $this->put(route('comments.update', $comment), ['body' => 'Changed'])->assertForbidden();
    });
});

describe('deleting', function () {
    test('authors can delete their comments', function () {
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id]);
        Event::fake([IssueTimelineChanged::class]);

        $this->delete(route('comments.destroy', $comment))->assertSessionHasNoErrors();

        expect(Comment::find($comment->id))->toBeNull();
        Event::assertDispatched(IssueTimelineChanged::class);
    });

    test('board admins can delete anyone\'s comments but members cannot', function () {
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id]);

        $this->actingAs(boardMember($this->board))->delete(route('comments.destroy', $comment))->assertForbidden();
        expect(Comment::find($comment->id))->not->toBeNull();

        $this->actingAs(boardMember($this->board, BoardRole::Admin))->delete(route('comments.destroy', $comment))->assertSessionHasNoErrors();
        expect(Comment::find($comment->id))->toBeNull();
    });

    test('comments whose author was deleted can still be removed by an admin', function () {
        $comment = Comment::factory()->for($this->issue)->create(['user_id' => null]);

        $this->actingAs(boardMember($this->board))->delete(route('comments.destroy', $comment))->assertForbidden();
        $this->actingAs(boardMember($this->board, BoardRole::Admin))->delete(route('comments.destroy', $comment))->assertSessionHasNoErrors();

        expect(Comment::find($comment->id))->toBeNull();
    });
});

describe('markdown', function () {
    test('basic formatting is rendered', function () {
        $comment = Comment::factory()->make(['body' => "Some **bold** and `code`\n\n- one\n- two"]);

        expect($comment->body_html)->toContain('<strong>bold</strong>')
            ->toContain('<code>code</code>')
            ->toContain('<li>one</li>');
    });

    test('raw html is removed', function (string $body) {
        $html = Comment::factory()->make(['body' => $body])->body_html;

        expect($html)->not->toContain('<script')->not->toContain('<img')->not->toContain('onerror')->not->toContain('<iframe')->not->toContain('<style');
    })->with([
        'script' => ['<script>alert(1)</script>hello'],
        'image handler' => ['<img src=x onerror=alert(1)>'],
        'iframe' => ['<iframe src="https://evil.test"></iframe>'],
        'style' => ['<style>body{display:none}</style>'],
    ]);

    test('unsafe link schemes are neutralized', function (string $body) {
        $html = Comment::factory()->make(['body' => $body])->body_html;

        expect($html)->not->toMatch('/href\s*=\s*["\']?\s*(javascript|data|vbscript):/i');
    })->with([
        'javascript' => ['[click](javascript:alert(1))'],
        'mixed case' => ['[click](JaVaScRiPt:alert(1))'],
        'data' => ['[click](data:text/html;base64,PHNjcmlwdD4=)'],
        'autolink' => ['<javascript:alert(1)>'],
    ]);

    test('images are shown as links instead of being loaded', function () {
        $html = Comment::factory()->make(['body' => '![A diagram](https://example.com/diagram.png) and ![](https://example.com/b.png)'])->body_html;

        expect($html)->not->toContain('<img')
            ->toContain('<a href="https://example.com/diagram.png" target="_blank" rel="nofollow noopener noreferrer">A diagram</a>')
            ->toContain('>image</a>');
    });

    test('links to other sites open safely in a new tab', function () {
        config(['app.url' => 'https://nagare.test']);

        $external = Comment::factory()->make(['body' => '[docs](https://example.com/docs)'])->body_html;
        $internal = Comment::factory()->make(['body' => '[board](https://nagare.test/boards/1)'])->body_html;

        expect($external)->toContain('href="https://example.com/docs"')->toContain('target="_blank"')->toContain('noopener')->toContain('noreferrer')->toContain('nofollow')
            ->and($internal)->not->toContain('target="_blank"');
    });

    test('the page receives the rendered html, not unsafe input', function () {
        Comment::factory()->for($this->issue)->create(['user_id' => $this->author->id, 'body' => "<script>alert(1)</script>\n\n**ok**"]);

        $props = $this->get(route('issues.show', $this->issue))->assertOk();

        $props->assertInertia(fn ($page) => $page->loadDeferredProps(fn ($deferred) => $deferred
            ->where('timeline.0.body_html', fn ($html) => str_contains($html, '<strong>ok</strong>') && ! str_contains($html, '<script'))));
    });
});
