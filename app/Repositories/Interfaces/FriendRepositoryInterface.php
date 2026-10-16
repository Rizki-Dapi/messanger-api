<?php

namespace App\Repositories\Interfaces;

use App\Models\Friend;
use Illuminate\Database\Eloquent\Collection;

interface FriendRepositoryInterface
{
    public function getByUserId(int $userId): Collection;

    public function findById(int $id): ?Friend;

    public function isFriendshipExists(int $userId, int $friendId): bool;

    public function getMutualFriends(int $userId): Collection;

    public function create(array $data): Friend;

    public function delete(Friend $friend): void;
}
