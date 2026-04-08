<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Mood;
use App\Enums\ReminderType;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => fake()->optional()->paragraph(),
            'remind_at' => now()->addDay(),
            'mood' => fake()->randomElement(Mood::cases()),
            'reminder_type' => fake()->randomElement(ReminderType::cases()),
            'is_completed' => false,
        ];
    }
}
