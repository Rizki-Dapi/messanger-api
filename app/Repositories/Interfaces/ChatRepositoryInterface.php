<?php

namespace App\Repositories\Interfaces;

use App\Models\Chat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface ChatRepositoryInterface
{
    public function getByConversationId(int $conversationId): Collection;

    public function getUnreadCount(int $conversationId, int $senderId): int;

    public function getLastChatUnreadChatId(int $conversationId, int $senderId): ?int;

    public function create(array $data): Chat;

    public function update(Chat $chat, array $data): Chat;

    public function markUnreadAsRead(int $conversationId, int $senderId, Carbon $readAt): int;

    public function delete(Chat $chat): bool;

    public function invalidateUnreadCountCache(int $conversationId, int $userId): void;
}
