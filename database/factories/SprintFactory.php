<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sprint>
 */
final class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = (clone $start)->modify('+2 weeks');

        return [
            'board_id' => Board::factory(),
            'slug' => fake()->unique()->slug(2),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];
    }
}
