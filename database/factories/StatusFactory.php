<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'name' => fake()->word(),
            'sort' => 0,
            'is_closed' => false,
        ];
    }

    /**
     * Mark the status as a closing status.
     */
    public function closed(): static
    {
        return $this->state(['is_closed' => true]);
    }
}
