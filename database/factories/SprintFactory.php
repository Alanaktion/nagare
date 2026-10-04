<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Sprint;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::instance(fake()->dateTimeBetween('-1 year', 'now'))->startOfDay();

        return [
            'board_id' => Board::factory()->withSprints(),
            'slug' => $start->format('Y-m-d'),
            'start_date' => $start,
            'end_date' => $start->addDays(13),
            'closed_at' => null,
        ];
    }

    /**
     * A sprint covering today.
     */
    public function current(): static
    {
        return $this->between(today()->subDays(3), today()->addDays(10));
    }

    /**
     * A sprint with the given first and last day. The slug is the start date.
     */
    public function between(CarbonInterface $start, CarbonInterface $end): static
    {
        return $this->state([
            'slug' => $start->format('Y-m-d'),
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    /**
     * A closed sprint.
     */
    public function closed(): static
    {
        return $this->state(['closed_at' => now()]);
    }
}
