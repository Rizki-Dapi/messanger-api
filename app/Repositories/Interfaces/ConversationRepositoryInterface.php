<?php

namespace App\Repositories\Interfaces;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Collection;

interface ConversationRepositoryInterface
{
    public function findById(int $id): ?Conversation;

    public function findBetweenUsers(int $senderId, int $receiverId): ?Conversation;

    public function getByUserId(int $userId): Collection;

    public function create(array $data): Conversation;

    public function invalidateConversationCache(Conversation $conversation): void;
}
