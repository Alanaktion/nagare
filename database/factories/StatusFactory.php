<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
final class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'name' => fake()->word(),
            'sort' => fake()->numberBetween(1, 10),
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(['is_closed' => true]);
    }
}
