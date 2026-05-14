<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\Issue;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Issue>
 */
final class IssueFactory extends Factory
{
    protected $model = Issue::class;

    public function definition(): array
    {
        $board = Board::factory()->create();
        $status = Status::factory()->for($board)->create();

        return [
            'board_id' => $board->id,
            'status_id' => $status->id,
            'author_id' => User::factory(),
            'assigned_id' => null,
            'name' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'sort' => 0,
        ];
    }
}
