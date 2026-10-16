<?php

namespace App\Repositories;

use App\Models\Chat;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Override;

class ChatRepository implements ChatRepositoryInterface
{
    #[Override]
    public function getByConversationId(int $conversationId): Collection
    {
        return Chat::where('conversation_id', $conversationId)->get();
    }

    #[Override]
    public function getUnreadCount(int $conversationId, int $senderId): int
    {
        return Cache::store(config('cache.default'))->remember(
            "conversation:{$conversationId}:unread:{$senderId}",
            now()->addMinutes(5),
            fn () => Chat::query()
                ->where('conversation_id', $conversationId)
                ->where('sender_id', $senderId)
                ->whereNull('read_at')
                ->count()
        );
    }

    #[Override]
    public function getLastChatUnreadChatId(int $conversationId, int $senderId): ?int
    {
        $id = Chat::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', $senderId)
            ->whereNull('read_at')
            ->max('id');

        return $id === null ? null : $id;
    }

    #[Override]
    public function create(array $data): Chat
    {
        return Chat::create($data);
    }

    #[Override]
    public function update(Chat $chat, array $data): Chat
    {
        $chat->update($data);

        return $chat->refresh();
    }

    #[Override]
    public function markUnreadAsRead(int $conversationId, int $senderId, Carbon $readAt): int
    {
        return Chat::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', $senderId)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);
    }

    #[Override]
    public function delete(Chat $chat): bool
    {
        return $chat->delete();
    }

    #[Override]
    public function invalidateUnreadCountCache(int $conversationId, int $userId): void
    {
        $cache = Cache::store(config('cache.default'));

        $cache->forget("conversation:{$conversationId}:unread:{$userId}");
    }
}
