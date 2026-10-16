<?php

use App\Models\Story;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('caches story feed after retrieving it', function () {
    $user = createUser();

    $story = Story::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHours(24),
    ]);

    actingAsApi($user)
        ->getJson('/api/stories/index-feed')
        ->assertStatus(200);

    expect(Cache::has("stories:feed:{$user->id}"))->toBeTrue();
});

it('refreshes story feed after a new story is created', function () {
    $user = createUser();

    actingAsApi($user)
        ->getJson('/api/stories/index-feed')
        ->assertStatus(200);

    expect(Cache::has("stories:feed:{$user->id}"))->toBeTrue();

    actingAsApi($user)
        ->postJson('/api/stories', [
            'captions' => 'New story',
        ])
        ->assertSuccessful();

    expect(Cache::has("stories:feed:{$user->id}"))->toBeFalse();
});

it('invalidates story feed cache after a story is deleted', function () {
    $user = createUser();

    $story = Story::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHours(24),
    ]);

    actingAsApi($user)
        ->getJson('/api/stories/index-feed')
        ->assertStatus(200);

    expect(Cache::has("stories:feed:{$user->id}"))->toBeTrue();

    actingAsApi($user)
        ->deleteJson("/api/stories/{$story->id}")
        ->assertSuccessful();

    expect(Cache::has("stories:feed:{$user->id}"))->toBeFalse();
});
