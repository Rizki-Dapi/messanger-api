<?php

namespace App\Services;

use App\Exceptions\AlreadyFriendException;
use App\Models\Friend;
use App\Models\User;
use App\Repositories\Interfaces\FriendRepositoryInterface;
use App\Repositories\Interfaces\LogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FriendService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly FriendRepositoryInterface $friendRepo,
        private readonly LogRepositoryInterface $logRepo,
    ) {}

    public function getFriends(User $user): Collection
    {
        return $this->friendRepo->getByUserId($user->id)->load('friend');
    }

    public function findFriendById(Friend $friend, User $user): ?Friend
    {
        Gate::forUser($user)->authorize('view', $friend);

        return $this->friendRepo->findById($friend->id)->load('friend');
    }

    public function addFriend(int $friendId, User $user): Friend
    {
        if ($user->id === $friendId) {
            throw ValidationException::withMessages([
                'message' => 'You cannot add yourself as a friend',
            ]);
        }

        if ($this->isFriend($user, $friendId)) {
            throw new AlreadyFriendException;
        }

        $this->logRepo->record([
            'user_id' => $user->id,
            'action' => 'user.add_friend',
        ]);

        return $this->friendRepo->create(
            [
                'user_id' => $user->id,
                'friend_id' => $friendId,
            ]
        );
    }

    public function unFriend(Friend $friend, User $user): void
    {
        Gate::forUser($user)->authorize('delete', $friend);

        if ($friend->user_id !== $user->id || ! $this->isFriend($user, $friend->friend_id)) {
            throw new ModelNotFoundException('frind not found');
        }

        $this->logRepo->record([
            'user_id' => $user->id,
            'action' => 'user.unfriend',
        ]);

        $this->friendRepo->delete($friend);
    }

    private function isFriend(User $user, int $friendId): bool
    {
        if ($user->id === $friendId) {
            return false;
        }

        return $this->friendRepo->isFriendshipExists($user->id, $friendId);
    }
}
