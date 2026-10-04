<?php

use App\Events\BoardDeleted;
use App\Events\BoardUpdated;
use App\Events\IssueCreated;
use App\Events\IssueDeleted;
use App\Events\IssueUpdated;
use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->board = Board::factory()->withDefaultStatuses()->withMember($this->user)->create();
    [$this->todo, $this->progress] = $this->board->statuses;
    Event::fake([IssueCreated::class, IssueUpdated::class, IssueDeleted::class, BoardUpdated::class, BoardDeleted::class]);
});

function channelFor(Board $board): string
{
    return 'boards.'.$board->id;
}

test('only board members can listen to the board channel', function () {
    $authorize = Broadcast::driver()->getChannels()->get('boards.{board}');

    expect($authorize($this->user, $this->board))->toBeTrue()
        ->and($authorize(User::factory()->create(), $this->board))->toBeFalse();
});

test('creating an issue broadcasts it on the board channel', function () {
    $this->actingAs($this->user)->post(route('boards.issues.store', $this->board), ['name' => 'Fresh', 'role' => 'task']);

    Event::assertDispatched(IssueCreated::class, function (IssueCreated $event) {
        expect($event->broadcastOn())->toEqual(new PrivateChannel(channelFor($this->board)))
            ->and($event->broadcastAs())->toBe('issue.created')
            ->and($event->broadcastWith()['issue']['name'])->toBe('Fresh');

        return true;
    });
});

test('updating an issue broadcasts its new state', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->actingAs($this->user)->put(route('issues.update', $issue), ['status_id' => $this->progress->id, 'sort' => 3]);

    Event::assertDispatched(IssueUpdated::class, function (IssueUpdated $event) use ($issue) {
        expect($event->broadcastAs())->toBe('issue.updated')
            ->and($event->broadcastWith()['issue']['id'])->toBe($issue->id)
            ->and($event->broadcastWith()['issue']['status_id'])->toBe($this->progress->id)
            ->and($event->broadcastWith()['sorts'])->toBeNull();

        return true;
    });
});

test('an update that renumbers a column broadcasts the new sort values', function () {
    $first = Issue::factory()->inStatus($this->todo)->create(['sort' => 1]);
    $second = Issue::factory()->inStatus($this->todo)->create(['sort' => 1.00001]);

    $this->actingAs($this->user)->put(route('issues.update', $second), ['sort' => 1.00002]);

    Event::assertDispatched(IssueUpdated::class, fn (IssueUpdated $event) => $event->broadcastWith()['sorts'] === [$first->id => 1.0, $second->id => 2.0]);
});

test('a rejected update broadcasts nothing', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->actingAs($this->user)->put(route('issues.update', $issue), ['name' => '']);
    $this->actingAs(User::factory()->create())->put(route('issues.update', $issue), ['name' => 'x']);

    Event::assertNotDispatched(IssueUpdated::class);
});

test('deleting an issue broadcasts its id', function () {
    $issue = Issue::factory()->inStatus($this->todo)->create();

    $this->actingAs($this->user)->delete(route('issues.destroy', $issue));

    Event::assertDispatched(IssueDeleted::class, fn (IssueDeleted $event) => $event->broadcastWith() === ['id' => $issue->id]
        && $event->broadcastOn()->name === 'private-'.channelFor($this->board));
});

test('editing board statuses tells viewers to reload the board', function () {
    $this->actingAs($this->user)->put(route('boards.update', $this->board), [
        'name' => 'Renamed',
        'statuses' => [['id' => $this->todo->id, 'name' => 'Backlog']],
    ]);

    Event::assertDispatched(BoardUpdated::class, fn (BoardUpdated $event) => $event->boardId === $this->board->id);
});

test('deleting a board tells viewers it is gone', function () {
    $admin = User::factory()->create();
    $this->board->users()->attach($admin, ['role' => 'admin']);

    $this->actingAs($admin)->delete(route('boards.destroy', $this->board));

    Event::assertDispatched(BoardDeleted::class, fn (BoardDeleted $event) => $event->boardId === $this->board->id);
});
