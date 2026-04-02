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
            ['email' => env('SEED_USER1_EMAIL', 'enricpuigdemontverger@gmail.com')],
            [
                'name' => env('SEED_USER1_NAME', 'Enric'),
                'username' => env('SEED_USER1_USERNAME', 'enricpuigdemontverger'),
                'password' => env('SEED_USER1_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => env('SEED_USER2_EMAIL', 'sheilaovelleiro@hotmail.com')],
            [
                'name' => env('SEED_USER2_NAME', 'Sheila'),
                'username' => env('SEED_USER2_USERNAME', 'sheilaovelleiro'),
                'password' => env('SEED_USER2_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
