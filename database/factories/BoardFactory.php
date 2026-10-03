<?php

namespace Database\Factories;

use App\Enums\BoardRole;
use App\Enums\SprintCycle;
use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'has_stories' => false,
            'has_sprints' => false,
            'sprint_cycle' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Enable stories on the board.
     */
    public function withStories(): static
    {
        return $this->state(['has_stories' => true]);
    }

    /**
     * Enable sprints on the board.
     */
    public function withSprints(SprintCycle $cycle = SprintCycle::Weekly): static
    {
        return $this->state(['has_sprints' => true, 'sprint_cycle' => $cycle]);
    }

    /**
     * Give the board the default To Do / In Progress / Done statuses.
     */
    public function withDefaultStatuses(): static
    {
        return $this->afterCreating(function (Board $board): void {
            $board->statuses()->createMany([
                ['name' => 'To Do', 'sort' => 0],
                ['name' => 'In Progress', 'sort' => 1],
                ['name' => 'Done', 'sort' => 2, 'is_closed' => true],
            ]);
        });
    }

    /**
     * Attach a user to the board with the given role.
     */
    public function withMember(User $user, BoardRole $role = BoardRole::Member): static
    {
        return $this->afterCreating(function (Board $board) use ($user, $role): void {
            $board->users()->attach($user, ['role' => $role->value]);
        });
    }

    /**
     * Attach a user as a board admin.
     */
    public function withAdmin(User $user): static
    {
        return $this->withMember($user, BoardRole::Admin);
    }
}
