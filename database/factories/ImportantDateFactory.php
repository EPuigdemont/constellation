<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImportantDate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportantDate>
 */
class ImportantDateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->sentence(3),
            'date' => fake()->date(),
            'recurs_annually' => false,
        ];
    }

    public function recurring(): static
    {
        return $this->state(['recurs_annually' => true]);
    }
}
