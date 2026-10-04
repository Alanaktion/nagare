<?php

use App\Models\Board;
use App\Models\Comment;
use App\Models\IssueActivity;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('demo data covers every kind of board and opens for the demo user', function () {
    $this->seed(DemoSeeder::class);

    $user = User::where('email', 'test@example.com')->sole();
    $boards = $user->boards()->get();

    expect($boards)->toHaveCount(4)
        ->and($boards->map(fn (Board $board) => [$board->has_stories, $board->has_sprints])->sort()->values()->all())
        ->toBe([[false, false], [false, true], [true, false], [true, true]]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    expect(Comment::count())->toBeGreaterThan(0)->and(IssueActivity::count())->toBeGreaterThan(0);

    foreach ($boards as $board) {
        $this->followingRedirects()->get(route('boards.show', $board))->assertOk();
    }
});

test('seeding twice does not duplicate the demo boards', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Board::count())->toBe(4)
        ->and(User::where('email', 'test@example.com')->count())->toBe(1);
});
