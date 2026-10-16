<?php

namespace Database\Factories;

use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 */
class StoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'captions' => fake()->sentence(),
            'media' => fake()->optional()->randomElement([
                'stories/'.fake()->uuid().'.jpg',
                'stories/'.fake()->uuid().'.mp4',
            ]),
            'expires_at' => fake()->dateTimeBetween('now', '+24 hours'),
        ];
    }
}
