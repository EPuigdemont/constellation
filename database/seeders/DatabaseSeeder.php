<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TagSeeder::class);

        User::firstOrCreate(
            ['email' => env('SEED_USER1_EMAIL', 'user1@example.com')],
            [
                'name' => env('SEED_USER1_NAME', 'User One'),
                'username' => env('SEED_USER1_USERNAME', 'user1'),
                'password' => env('SEED_USER1_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => env('SEED_USER2_EMAIL', 'user2@example.com')],
            [
                'name' => env('SEED_USER2_NAME', 'User Two'),
                'username' => env('SEED_USER2_USERNAME', 'user2'),
                'password' => env('SEED_USER2_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
