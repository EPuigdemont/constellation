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
            ['email' => env('SEED_USER1_EMAIL', 'enric@constellation.com')],
            [
                'name' => env('SEED_USER1_NAME', 'Enric'),
                'username' => env('SEED_USER1_USERNAME', 'enric'),
                'password' => env('SEED_USER1_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => env('SEED_USER2_EMAIL', 'sheila@constellation.com')],
            [
                'name' => env('SEED_USER2_NAME', 'Sheila'),
                'username' => env('SEED_USER2_USERNAME', 'sheila'),
                'password' => env('SEED_USER2_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
