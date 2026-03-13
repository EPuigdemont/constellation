<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Mood;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->optional()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'mood' => fake()->randomElement(Mood::cases()),
            'color_override' => null,
            'is_public' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }
}
