<?php

use App\Models\Friend;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Cache::flush();

    $this->owner = createUser();
    $this->viewer = createUser();
});

it('only return own stories and mutual friends stories in feed', function () {
    $user = createUser();
    $mutualFriend = createUser();
    $oneWayFriend = createUser();

    Friend::factory()->create([
        'user_id' => $user->id,
        'friend_id' => $mutualFriend->id,
    ]);

    Friend::factory()->create([
        'user_id' => $mutualFriend->id,
        'friend_id' => $user->id,
    ]);

    Friend::factory()->create([
        'user_id' => $user->id,
        'friend_id' => $oneWayFriend->id,
    ]);

    $ownStory = Story::factory()->create(['user_id' => $user->id]);
    $mutualStory = Story::factory()->create(['user_id' => $mutualFriend->id]);
    Story::factory()->create(['user_id' => $oneWayFriend->id]);

    $respone = actingAsApi($user)
        ->getJson('/api/stories/index-feed')
        ->assertStatus(200);

    $storyIds = collect($respone->json('data'))
        ->pluck('id')
        ->all();

    expect($storyIds)
        ->toContain($ownStory->id)
        ->toContain($mutualStory->id)
        ->toHaveCount(2);
});

it('does not return expired stories in feed', function () {
    $user = createUser();

    $activeStory = Story::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHours(24),
    ]);

    Story::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->subMinute(),
    ]);

    $response = actingAsApi($user)
        ->getJson('/api/stories/index-feed')
        ->assertStatus(200);

    $storyIds = collect($response->json('data'))
        ->pluck('id')
        ->all();

    expect($storyIds)
        ->toContain($activeStory->id)
        ->toHaveCount(1);
});

it('records a story view when a user views a story', function () {
    $user = createUser();
    $viewer = createUser();

    Friend::factory()->create([
        'user_id' => $user->id,
        'friend_id' => $viewer->id,
    ]);

    Friend::factory()->create([
        'user_id' => $viewer->id,
        'friend_id' => $user->id,
    ]);

    $story = Story::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHours(24),
    ]);

    actingAsApi($viewer)->getJson("/api/stories/view/{$story->id}")->assertStatus(200);

    assertDatabaseHas('story_views', [
        'story_id' => $story->id,
        'user_id' => $viewer->id,
    ]);
});

it('does not allow a non-mutual friend to view a story', function () {
    $user = createUser();
    $viewer = createUser();

    $story = Story::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHours(24),
    ]);

    actingAsApi($viewer)->getJson("/api/stories/view/{$story->id}")->assertStatus(403);

    expect(
        StoryView::where('story_id', $story->id)
            ->where('user_id', $viewer->id)
            ->exists()
    )->toBeFalse();
});

it('does not create duplicate story views when viewing the same story multiple times', function () {
    $user = createUser();
    $viewer = createUser();

    Friend::factory()->create([
        'user_id' => $user->id,
        'friend_id' => $viewer->id,
    ]);

    Friend::factory()->create([
        'user_id' => $viewer->id,
        'friend_id' => $user->id,
    ]);

    $story = Story::factory()->create([
        'user_id' => $user->id,
        'expires_at' => now()->addHours(24),
    ]);

    actingAsApi($viewer)->getJson("/api/stories/view/{$story->id}")->assertStatus(200);
    actingAsApi($viewer)->getJson("/api/stories/view/{$story->id}")->assertStatus(200);

    expect(
        StoryView::where('story_id', $story->id)
            ->where('user_id', $viewer->id)
            ->count()
    )->toBe(1);
});

it('does not allow a user to view an expired story', function () {
    $creator = createUser();
    $viewer = createUser();

    Friend::factory()->create([
        'user_id' => $creator->id,
        'friend_id' => $viewer->id,
    ]);

    Friend::factory()->create([
        'user_id' => $viewer->id,
        'friend_id' => $creator->id,
    ]);

    $story = Story::factory()->create([
        'user_id' => $creator->id,
        'expires_at' => now()->subMinute(),
    ]);

    actingAsApi($viewer)->getJson("/api/stories/view/{$story->id}")->assertStatus(404);

    expect(
        StoryView::where('story_id', $story->id)
            ->where('user_id', $viewer->id)
            ->exists()
    )->toBeFalse();
});

it('allows the story owner to see users who viewed their story', function () {
    $creator = createUser();
    $viewer = createUser();

    $story = Story::factory()->create([
        'user_id' => $creator->id,
        'expires_at' => now()->addHours(24),
    ]);

    StoryView::factory()->create([
        'story_id' => $story->id,
        'user_id' => $viewer->id,
    ]);

    actingAsApi($creator)
        ->getJson("/api/stories/index-viewers/{$story->id}")
        ->assertStatus(200)
        ->assertJsonFragment([
            'id' => $viewer->id,
            'name' => $viewer->name,
        ]);
});

it('does not allow a non-owner to see story viewers', function () {
    $creator = createUser();
    $viewer = createUser();
    $otherUser = createUser();

    $story = Story::factory()->create([
        'user_id' => $creator->id,
        'expires_at' => now()->addHours(24),
    ]);

    StoryView::factory()->create([
        'story_id' => $story->id,
        'user_id' => $viewer->id,
    ]);

    actingAsApi($otherUser)
        ->getJson("/api/stories/index-viewers/{$story->id}")
        ->assertStatus(403);
});

function makeMutualFriends(User $first, User $second): void
{
    Friend::query()->create([
        'user_id' => $first->id,
        'friend_id' => $second->id,
    ]);

    Friend::query()->create([
        'user_id' => $second->id,
        'friend_id' => $first->id,
    ]);
}
it('returns all active stories for a mutual friend', function () {
    makeMutualFriends($this->viewer, $this->owner);

    Story::factory()->create([
        'user_id' => $this->owner->id,
        'expires_at' => now()->addHours(10),
    ]);

    Story::factory()->create([
        'user_id' => $this->owner->id,
        'expires_at' => now()->addHours(20),
    ]);

    actingAsApi($this->viewer)
        ->getJson("/api/stories/index-user-story/{$this->owner->id}")
        ->assertOk()
        ->assertJsonPath(
            'message',
            'view story user retrieved successfully'
        )
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.user_view.id', $this->owner->id)
        ->assertJsonPath('data.1.user_view.id', $this->owner->id);
});

it('does not return stories to a user who is not a mutual friend', function () {
    Story::factory()->create([
        'user_id' => $this->owner->id,
        'expires_at' => now()->addHours(10),
    ]);

    actingAsApi($this->viewer)
        ->getJson("/api/stories/index-user-story/{$this->owner->id}")
        ->assertForbidden();
});

it('returns not found when the user has no active stories', function () {
    makeMutualFriends($this->viewer, $this->owner);

    Story::factory()->create([
        'user_id' => $this->owner->id,
        'expires_at' => now()->subMinute(),
    ]);

    actingAsApi($this->viewer)
        ->getJson("/api/stories/index-user-story/{$this->owner->id}")
        ->assertNotFound();
});

it('allows the owner to retrieve their own active stories', function () {
    Story::factory()->create([
        'user_id' => $this->owner->id,
        'expires_at' => now()->addHours(10),
    ]);

    Story::factory()->create([
        'user_id' => $this->owner->id,
        'expires_at' => now()->addHours(20),
    ]);

    actingAsApi($this->owner)
        ->getJson("/api/stories/index-user-story/{$this->owner->id}")
        ->assertOk()
        ->assertJsonPath(
            'message',
            'view story user retrieved successfully'
        )
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.user_view.id', $this->owner->id)
        ->assertJsonPath('data.1.user_view.id', $this->owner->id);
});
