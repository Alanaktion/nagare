<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Board>
 */
final class BoardFactory extends Factory
{
    protected $model = Board::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement([Board::TYPE_KANBAN, Board::TYPE_SCRUM]),
            'user_id' => User::factory(),
        ];
    }

    public function kanban(): static
    {
        return $this->state(['type' => Board::TYPE_KANBAN]);
    }

    public function scrum(): static
    {
        return $this->state(['type' => Board::TYPE_SCRUM]);
    }
}
