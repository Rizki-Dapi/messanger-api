<?php

namespace App\Services;

use App\Events\MessageReadEvent;
use App\Events\MessageSentEvent;
use App\Models\Chat;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Interfaces\ChatRepositoryInterface;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use function Illuminate\Support\now;

class ChatService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly ChatRepositoryInterface $chatRepo,
        private readonly ConversationRepositoryInterface $conversationRepo,
    ) {}

    public function getChats(int $conversationId, User $user): Collection
    {
        $conversation = $this->findConversation($conversationId);

        Gate::forUser($user)->authorize('view', $conversation);

        return $this->chatRepo->getByConversationId($conversation->id)->load('sender');
    }

    public function getConversation(User $user): Collection
    {
        return $this->conversationRepo->getByUserId($user->id)->load(['sender', 'receiver']);
    }

    public function sendChat(int $receiverId, string $message, User $user): Chat
    {
        $conversation = $this->findOrCreateConversation($user->id, $receiverId);

        Gate::forUser($user)->authorize('view', $conversation);

        $chat = $this->chatRepo->create(
            [
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $message,
            ]
        );

        $this->conversationRepo->invalidateConversationCache($conversation);

        $receiverId = $conversation->otherParticipantId($user);

        MessageSentEvent::dispatch($chat, $receiverId);

        $this->chatRepo->invalidateUnreadCountCache($conversation->id, $user->id);

        return $chat->load('sender');
    }

    public function sendChatToYourself(string $message, User $user): Chat
    {
        $conversation = $this->findOrCreateConversation($user->id, $user->id);

        Gate::forUser($user)->authorize('view', $conversation);

        $chat = $this->chatRepo->create(
            [
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $message,
            ]
        );

        $this->conversationRepo->invalidateConversationCache($conversation);

        $this->chatRepo->invalidateUnreadCountCache($conversation->id, $user->id);

        return $chat->load('sender');
    }

    public function markAsRead(int $conversationId, User $user): void
    {
        $conversation = $this->findConversation($conversationId);

        Gate::forUser($user)->authorize('view', $conversation);

        $senderId = $conversation->otherParticipantId($user);

        DB::transaction(function () use ($conversation, $senderId): void {
            $lastReadChatId = $this->chatRepo->getLastChatUnreadChatId($conversation->id, $senderId);

            if ($lastReadChatId === null) {
                return;
            }

            $readAt = now();

            $this->chatRepo->markUnreadAsRead($conversation->id, $senderId, $readAt);

            $this->chatRepo->invalidateUnreadCountCache($conversation->id, $senderId);

            MessageReadEvent::dispatch(
                $conversation->id,
                $senderId,
                $lastReadChatId,
                $readAt->toIso8601String(),
            );
        });
    }

    public function getUnreadCount(int $conversationId, User $user): int
    {
        $conversation = $this->findConversation($conversationId);

        Gate::forUser($user)->authorize('view', $conversation);

        $senderId = $conversation->otherParticipantId($user);

        return $this->chatRepo->getUnreadCount($conversation->id, $senderId);
    }

    public function update(Chat $chat, string $message, User $user): Chat
    {
        Gate::forUser($user)->authorize('update', $chat);

        $chat->message = $message;

        $chat->loadMissing('conversation');

        $conversation = $chat->conversation;

        $updateChat = $this->chatRepo->update($chat, ['message' => $message]);

        $this->conversationRepo->invalidateConversationCache($conversation);

        return $updateChat;
    }

    public function delete(Chat $chat, User $user): void
    {
        Gate::forUser($user)->authorize('delete', $chat);

        $chat->loadMissing('conversation');

        $conversation = $chat->conversation;

        $this->conversationRepo->invalidateConversationCache($conversation);

        $this->chatRepo->delete($chat);
    }

    private function findOrCreateConversation(int $senderId, int $receiverId): Conversation
    {
        return DB::transaction(function () use ($senderId, $receiverId) {
            $conversation = $this->conversationRepo->findBetweenUsers($senderId, $receiverId);

            if ($conversation) {
                return $conversation;
            }

            return $this->conversationRepo->create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
            ]);
        });
    }

    private function findConversation(int $conversationId): Conversation
    {
        $conversation = $this->conversationRepo->findById($conversationId);

        if (! $conversation) {
            throw new ModelNotFoundException('Conversation not found');
        }

        return $conversation;
    }
}
