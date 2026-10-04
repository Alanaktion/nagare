<?php

namespace Database\Factories;

use App\Enums\IssueRole;
use App\Models\Board;
use App\Models\Issue;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
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
            'status_id' => fn (array $attributes) => Status::factory()->create(['board_id' => $attributes['board_id']])->id,
            'role' => IssueRole::Task,
            'author_id' => User::factory(),
            'name' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'sort' => fake()->randomFloat(3, 0, 1000),
        ];
    }

    /**
     * Make the issue an epic.
     */
    public function epic(): static
    {
        return $this->state(['role' => IssueRole::Epic]);
    }

    /**
     * Make the issue a story.
     */
    public function story(): static
    {
        return $this->state(['role' => IssueRole::Story]);
    }

    /**
     * Place the issue in the given status.
     */
    public function inStatus(Status $status): static
    {
        return $this->state(['board_id' => $status->board_id, 'status_id' => $status->id]);
    }
}
