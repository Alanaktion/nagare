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
        return [
            'board_id' => Board::factory(),
            'status_id' => Status::factory(),
            'author_id' => User::factory(),
            'assigned_id' => null,
            'name' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'sort' => 0,
        ];
    }
}
