<?php

namespace App\Repositories;

use App\Models\Friend;
use App\Models\Story;
use App\Repositories\Interfaces\StoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Override;

class StoryRepository implements StoryRepositoryInterface
{
    #[Override]
    public function findById(int $id): ?Story
    {
        return Story::find($id);
    }

    #[Override]
    public function getActiveStoriesByUserId(int $userId): Collection
    {
        return Story::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->get();
    }

    #[Override]
    public function getFriendsStories(int $userId): Collection
    {
        return Story::whereIn(
            'user_id',
            Friend::mutualOf($userId)->select('friend_id')
        )
            ->where('expires_at', '>', now())
            ->get();
    }

    #[Override]
    public function getStoryFeed(int $userId): Collection
    {
        return Cache::store(config('cache.default'))->remember(
            "stories:feed:{$userId}",
            now()->addMinutes(5),
            fn () => Story::query()
                ->where('expires_at', '>', now())
                ->where(
                    fn ($query) => $query
                        ->where('user_id', $userId)
                        ->orWhereIn(
                            'user_id',
                            Friend::mutualOf($userId)->select('friend_id')
                        )
                )
                ->with('user')
                ->withExists([
                    'views as seen' => fn ($query) => $query
                        ->where('user_id', $userId),
                ])
                ->get()
        );
    }

    #[Override]
    public function create(array $data): Story
    {
        $story = Story::create($data);

        $this->invalidateStoryFeedCache($story->user_id);

        return $story;
    }

    #[Override]
    public function update(Story $story, array $data): Story
    {
        $story->update($data);
        $story = $story->refresh();

        $this->invalidateStoryFeedCache($story->user_id);

        return $story;
    }

    #[Override]
    public function delete(Story $story): bool
    {
        $userId = $story->user_id;

        $deleted = $story->delete();

        if ($deleted) {
            $this->invalidateStoryFeedCache($userId);
        }

        return $deleted;
    }

    private function invalidateStoryFeedCache(int $storyOwnerId): void
    {
        $userIds = Friend::mutualOf($storyOwnerId)
            ->pluck('friend_id')
            ->push($storyOwnerId)
            ->unique();

        $cache = Cache::store(config('cache.default'));

        foreach ($userIds as $userId) {
            $cache->forget("stories:feed:{$userId}");
        }
    }
}
