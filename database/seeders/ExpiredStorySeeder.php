<?php

namespace Database\Seeders;

use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpiredStorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrFail();

        Story::factory()->count(3)->create([
            'user_id' => $user->id,
            'expires_at' => now()->subHours(2),
            'media' => null,
        ]);

        $this->command->info('Created 3 expired stories.');
    }
}
