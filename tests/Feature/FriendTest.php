<?php

use App\Models\Friend;

it('does not create duplicate friendship when adding an existing friend', function () {
    $user = createUser();
    $friend = createUser();

    Friend::factory()->create([
        'user_id' => $user->id,
        'friend_id' => $friend->id,
    ]);

    Friend::factory()->create([
        'user_id' => $friend->id,
        'friend_id' => $user->id,
    ]);

    actingAsApi($user)
        ->postJson("/api/friend/add/{$friend->id}")
        ->assertStatus(409);

    expect(
        Friend::where(function ($query) use ($user, $friend) {
            $query->where('user_id', $user->id)
                ->where('friend_id', $friend->id);
        })->orWhere(function ($query) use ($user, $friend) {
            $query->where('user_id', $friend->id)
                ->where('friend_id', $user->id);
        })->count()
    )->toBe(2);
});

it('does not allow a user to unfriend someone who is not their friend', function () {
    $user = createUser();
    $otherUser = createUser();

    actingAsApi($user)
        ->deleteJson("/api/friend/unfriend/{$otherUser->id}")
        ->assertStatus(404);

    expect(
        Friend::where('user_id', $user->id)
            ->where('friend_id', $otherUser->id)
            ->exists()
    )->toBeFalse();
});

it('does not allow a user to add themselves as a friend', function () {
    $user = createUser();

    actingAsApi($user)
        ->postJson("/api/friend/add/{$user->id}")
        ->assertStatus(422);

    expect(
        Friend::where('user_id', $user->id)
            ->where('friend_id', $user->id)
            ->exists()
    )->toBeFalse();
});
