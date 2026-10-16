<?php

use App\Events\MessageReadEvent;
use App\Events\MessageSentEvent;
use App\Models\Chat;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

it('stores a chat and dispatches a message sent event', function () {
    Event::fake([MessageSentEvent::class]);

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Hello!',
        ])
        ->assertSuccessful();

    $conversation = Conversation::query()
        ->where(function ($query) use ($sender, $receiver) {
            $query->where('sender_id', $sender->id)
                ->where('receiver_id', $receiver->id);
        })
        ->orWhere(function ($query) use ($sender, $receiver) {
            $query->where('sender_id', $receiver->id)
                ->where('receiver_id', $sender->id);
        })
        ->firstOrFail();

    $chat = Chat::query()
        ->where('conversation_id', $conversation->id)
        ->where('message', 'Hello!')
        ->firstOrFail();

    expect($chat->conversation_id)->toBe($conversation->id);

    Event::assertDispatched(MessageSentEvent::class);
});

it('returns unread chat count for the authenticated user', function () {
    $user = createUser();
    $receiver = createUser();

    $conversation = Conversation::create([
        'sender_id' => $user->id,
        'receiver_id' => $receiver->id,
    ]);

    actingAsApi($user)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertSuccessful();
});

it('marks chats as read in a conversation', function () {
    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Hello!',
        ])
        ->assertSuccessful();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    actingAsApi($receiver)
        ->putJson("/api/chat/mark-as-read/{$conversation->id}")
        ->assertSuccessful();
});

it('prevents a non-participant from viewing conversation chats', function () {
    $sender = createUser();
    $receiver = createUser();
    $outsider = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Private message',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    actingAsApi($outsider)
        ->getJson("/api/chat/index-chats/{$conversation->id}")
        ->assertForbidden();
});

it('prevents a user from updating another users chat', function () {
    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Original message',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    $chat = Chat::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $sender->id)
        ->where('message', 'Original message')
        ->firstOrFail();

    actingAsApi($receiver)
        ->putJson("/api/chat/update/{$chat->id}", [
            'message' => 'Changed message',
        ])
        ->assertForbidden();

    expect($chat->fresh()->message)->toBe('Original message');
});

it('marks incoming chats as read and dispatches a message read event', function () {
    Event::fake([MessageReadEvent::class]);

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Hello!',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    $chat = Chat::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $sender->id)
        ->where('message', 'Hello!')
        ->firstOrFail();

    expect($chat->read_at)->toBeNull();

    actingAsApi($receiver)
        ->putJson("/api/chat/mark-as-read/{$conversation->id}")
        ->assertSuccessful();

    expect($chat->fresh()->read_at)->not->toBeNull();

    Event::assertDispatched(MessageReadEvent::class);
});

it('counts only unread incoming chats for the authenticated user', function () {
    Cache::flush();

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'First message',
        ])
        ->assertCreated();

    actingAsApi($receiver)
        ->postJson("/api/chat/send-chat/{$sender->id}", [
            'message' => 'Reply message',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where(function ($query) use ($sender, $receiver) {
            $query->where('sender_id', $sender->id)
                ->where('receiver_id', $receiver->id);
        })
        ->orWhere(function ($query) use ($sender, $receiver) {
            $query->where('sender_id', $receiver->id)
                ->where('receiver_id', $sender->id);
        })
        ->firstOrFail();

    actingAsApi($receiver)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data', 1);
});

it('prevents a user from deleting another users chat', function () {
    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Original message',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    $chat = Chat::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $sender->id)
        ->where('message', 'Original message')
        ->firstOrFail();

    actingAsApi($receiver)
        ->deleteJson("/api/chat/{$chat->id}")
        ->assertForbidden();

    expect(
        Chat::query()
            ->whereKey($chat->id)
            ->where('message', 'Original message')
            ->exists()
    )->toBeTrue();
});

it('resets unread count after all incoming chats are marked as read', function () {
    Cache::flush();

    $sender = createUser();
    $receiver = createUser();

    foreach (['First message', 'Second message'] as $message) {
        actingAsApi($sender)
            ->postJson("/api/chat/send-chat/{$receiver->id}", [
                'message' => $message,
            ])
            ->assertCreated();
    }

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    actingAsApi($receiver)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data', 2);

    actingAsApi($receiver)
        ->putJson("/api/chat/mark-as-read/{$conversation->id}")
        ->assertSuccessful();

    actingAsApi($receiver)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data', 0);

    expect(
        Chat::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', $sender->id)
            ->whereNull('read_at')
            ->count()
    )->toBe(0);
});

it('invalidates conversation cache when a new chat is sent', function () {
    Cache::flush();

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->getJson('/api/chat/index-conversations')
        ->assertSuccessful()
        ->assertJsonPath('data', []);

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'New message after cache',
        ])
        ->assertCreated();

    actingAsApi($sender)
        ->getJson('/api/chat/index-conversations')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    expect(
        Conversation::query()
            ->where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->exists()
    )->toBeTrue();
});

it('stores a self-chat without dispatching a message sent event', function () {
    Event::fake([MessageSentEvent::class]);

    $user = createUser();

    actingAsApi($user)
        ->postJson('/api/chat/send-chat-to-yourself', [
            'message' => 'Note to myself',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $user->id)
        ->where('receiver_id', $user->id)
        ->firstOrFail();

    $chat = Chat::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $user->id)
        ->where('message', 'Note to myself')
        ->firstOrFail();

    expect($chat->message)->toBe('Note to myself');

    Event::assertNotDispatched(MessageSentEvent::class);
});

it('invalidates conversation cache when a chat is deleted', function () {
    Cache::flush();

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Message to delete',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    actingAsApi($sender)
        ->getJson('/api/chat/index-conversations')
        ->assertSuccessful();

    $chat = Chat::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $sender->id)
        ->where('message', 'Message to delete')
        ->firstOrFail();

    actingAsApi($sender)
        ->deleteJson("/api/chat/{$chat->id}")
        ->assertSuccessful();

    expect(
        Chat::query()->whereKey($chat->id)->exists()
    )->toBeFalse();

    expect(
        Conversation::query()->whereKey($conversation->id)->exists()
    )->toBeTrue();
});

it('prevents an outsider from retrieving a conversation unread count', function () {
    $sender = createUser();
    $receiver = createUser();
    $outsider = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Private message',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    actingAsApi($outsider)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertForbidden();
});

it('prevents an outsider from marking conversation chats as read', function () {
    $sender = createUser();
    $receiver = createUser();
    $outsider = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Private message',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    $chat = Chat::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_id', $sender->id)
        ->whereNull('read_at')
        ->firstOrFail();

    actingAsApi($outsider)
        ->putJson("/api/chat/mark-as-read/{$conversation->id}")
        ->assertForbidden();

    expect($chat->fresh()->read_at)->toBeNull();
});

it('increments unread count for a new message after previous chats were read', function () {
    config(['cache.default' => 'array']);

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'First message',
        ])
        ->assertCreated();

    $conversation = Conversation::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    actingAsApi($receiver)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data', 1);

    actingAsApi($receiver)
        ->putJson("/api/chat/mark-as-read/{$conversation->id}")
        ->assertSuccessful();

    actingAsApi($receiver)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data', 0);

    expect(
        Chat::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', $sender->id)
            ->whereNull('read_at')
            ->count()
    )->toBe(0);

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Second message',
        ])
        ->assertCreated();

    expect(
        Chat::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', $sender->id)
            ->whereNull('read_at')
            ->count()
    )->toBe(1);

    actingAsApi($receiver)
        ->getJson("/api/chat/unread-count/{$conversation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data', 1);
});
