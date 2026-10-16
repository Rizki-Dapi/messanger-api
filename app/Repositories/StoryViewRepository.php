<?php

namespace App\Repositories;

use App\Models\StoryView;
use App\Repositories\Interfaces\StoryViewRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class StoryViewRepository implements StoryViewRepositoryInterface
{
    #[Override]
    public function create(array $data): StoryView
    {
        return StoryView::create($data);
    }

    public function firstOrCreate(int $storyId, int $userId): StoryView
    {
        return StoryView::firstOrCreate([
            'story_id' => $storyId,
            'user_id' => $userId,
        ]);
    }

    #[Override]
    public function exists(int $storyId, int $userId): bool
    {
        return StoryView::where('story_id', $storyId)
            ->where('user_id', $userId)
            ->exists();
    }

    #[Override]
    public function getByStoryId(int $storyId): Collection
    {
        return StoryView::where('story_id', $storyId)->get();
    }
}
