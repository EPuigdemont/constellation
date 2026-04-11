<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'path' => 'images/'.fake()->uuid().'.jpg',
            'disk' => 'private',
            'alt' => fake()->optional()->sentence(),
            'image_width' => 1600,
            'image_height' => 900,
        ];
    }
}
