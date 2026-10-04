<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'comment_id' => null,
            'user_id' => User::factory(),
            'disk' => config('attachments.disk'),
            'path' => 'attachments/1/'.Str::ulid().'.pdf',
            'thumbnail_path' => null,
            'name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1_000, 5_000_000),
        ];
    }
}
