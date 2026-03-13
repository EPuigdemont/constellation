<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'user_id' => User::factory(),
            'color' => fake()->optional()->hexColor(),
        ];
    }

    public function system(): static
    {
        return $this->state(['user_id' => null]);
    }
}
