<?php

namespace App\Repositories\Interfaces;

use App\Models\Story;
use Illuminate\Database\Eloquent\Collection;

interface StoryRepositoryInterface
{
    public function findById(int $id): ?Story;

    public function getActiveStoriesByUserId(int $userId): Collection;

    public function getFriendsStories(int $userId): Collection;

    public function getStoryFeed(int $userId): Collection;

    public function create(array $data): Story;

    public function update(Story $story, array $data): Story;

    public function delete(Story $story): bool;
}
