<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $systemTags = [
            'happy',
            'sad',
            'reflective',
            'grateful',
            'anxious',
            'excited',
            'love',
            'memory',
            'goal',
            'dream',
        ];

        foreach ($systemTags as $name) {
            Tag::firstOrCreate(
                ['name' => $name, 'user_id' => null],
            );
        }
    }
}
