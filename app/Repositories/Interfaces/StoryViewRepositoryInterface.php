<?php

namespace App\Repositories\Interfaces;

use App\Models\StoryView;
use Illuminate\Database\Eloquent\Collection;

interface StoryViewRepositoryInterface
{
    public function create(array $data): StoryView;

    public function firstOrCreate(int $storyId, int $userId): StoryView;

    public function exists(int $storyId, int $userId): bool;

    public function getByStoryId(int $storyId): Collection;
}
