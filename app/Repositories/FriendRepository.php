<?php

namespace App\Repositories;

use App\Models\Friend;
use App\Repositories\Interfaces\FriendRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class FriendRepository implements FriendRepositoryInterface
{
    #[Override]
    public function getByUserId(int $userId): Collection
    {
        return Friend::where('user_id', $userId)->get();
    }

    #[Override]
    public function findById(int $id): ?Friend
    {
        return Friend::find($id);
    }

    #[Override]
    public function isFriendshipExists(int $userId, int $friendId): bool
    {
        return Friend::where('user_id', $userId)
            ->where('friend_id', $friendId)
            ->exists();
    }

    #[Override]
    public function getMutualFriends(int $userId): Collection
    {
        return Friend::mutualOf($userId)->get();
    }

    #[Override]
    public function create(array $data): Friend
    {
        return Friend::create($data);
    }

    #[Override]
    public function delete(Friend $friend): void
    {
        $friend->delete();
    }
}
