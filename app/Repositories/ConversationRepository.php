<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Override;

class ConversationRepository implements ConversationRepositoryInterface
{
    #[Override]
    public function findById(int $id): ?Conversation
    {
        return Conversation::find($id);
    }

    #[Override]
    public function findBetweenUsers(int $senderId, int $receiverId): ?Conversation
    {
        return Conversation::where(
            fn ($query) => $query
                ->where('sender_id', $senderId)
                ->where('receiver_id', $receiverId)
        )
            ->orWhere(
                fn ($query) => $query
                    ->where('sender_id', $receiverId)
                    ->where('receiver_id', $senderId)
            )
            ->first();
    }

    #[Override]
    public function getByUserId(int $userId): Collection
    {
        $cache = Cache::store(config('cache.default'));

        $convesations = $cache->remember(
            "conversation:list:{$userId}",
            now()->addMinutes(5),
            fn () => Conversation::query()
                ->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId)
                ->with('sender', 'receiver')
                ->get()
        );

        foreach ($convesations as $conversation) {
            $lastMessage = $cache->remember(
                "conversation:{$conversation->id}:last-message",
                now()->addMinutes(5),
                fn () => $conversation->lastMessage()->first()
            );

            $conversation->setRelation('lastMessage', $lastMessage);
        }

        return $convesations;
    }

    #[Override]
    public function create(array $data): Conversation
    {
        return Conversation::create($data);
    }

    #[Override]
    public function invalidateConversationCache(Conversation $conversation): void
    {
        $cache = Cache::store(config('cache.default'));

        $cache->forget("conversation:list:{$conversation->sender_id}");

        $cache->forget("conversation:list:{$conversation->receiver_id}");

        $cache->forget("conversation:{$conversation->id}:last-message");
    }
}
