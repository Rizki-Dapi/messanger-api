<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            [
                'email' => 'john@example.com',
            ],
            [
                'name' => 'john doe',
                'phone' => fake()->PhoneNumber(),
                'password' => bcrypt('Password123!'),
            ]
        );

        User::firstOrCreate(
            [
                'email' => 'jane@example.com',
            ],
            [
                'name' => 'jane doe',
                'phone' => fake()->PhoneNumber(),
                'password' => bcrypt('Password123!'),
            ]
        );

        User::firstOrCreate(
            [
                'email' => 'alex@example.com',
            ],
            [
                'name' => 'alex smith',
                'phone' => fake()->PhoneNumber(),
                'password' => bcrypt('Password123!'),
            ]
        );

        User::firstOrCreate(
            [
                'email' => 'kitasan@example.com',
            ],
            [
                'name' => 'kitasan black',
                'phone' => fake()->PhoneNumber(),
                'password' => bcrypt('Password123!'),
            ]
        );

        User::firstOrCreate(
            [
                'email' => 'tachyon@example.com',
            ],
            [
                'name' => 'agnes tachyon',
                'phone' => fake()->PhoneNumber(),
                'password' => bcrypt('Password123!'),
            ]
        );
    }
}
