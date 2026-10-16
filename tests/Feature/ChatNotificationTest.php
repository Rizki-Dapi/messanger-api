<?php

use App\Events\MessageSentEvent;
use App\Listeners\SendNewMessageNotificationListener;
use App\Models\Chat;
use App\Models\Conversation;
use App\Notifications\NewMesssageNotification;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('dispatches the message sent event when a chat is sent', function () {
    Event::fake([MessageSentEvent::class]);

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Hello notification!',
        ])
        ->assertCreated();

    Event::assertDispatched(
        MessageSentEvent::class,
        function (MessageSentEvent $event) use ($receiver) {
            return $event->receiverId === $receiver->id
                && $event->message->message === 'Hello notification!';
        }
    );
});

it('registers the notification listener for the message sent event', function () {
    $listeners = Event::getListeners(MessageSentEvent::class);

    expect($listeners)->not->toBeEmpty();
});

it('sends a new message notification to the receiver only', function () {
    Notification::fake();

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Hello notification!',
        ])
        ->assertCreated();

    $chat = Chat::query()
        ->where('sender_id', $sender->id)
        ->where('message', 'Hello notification!')
        ->firstOrFail();

    (new SendNewMessageNotificationListener)->handle(
        new MessageSentEvent($chat, $receiver->id)
    );

    Notification::assertSentTo(
        $receiver,
        NewMesssageNotification::class
    );

    Notification::assertNotSentTo(
        $sender,
        NewMesssageNotification::class
    );
});

it('stores the new message notification in the database', function () {
    $sender = createUser();
    $receiver = createUser();

    $conversation = Conversation::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
    ]);

    $chat = Chat::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
        'message' => 'Offline notification',
    ]);

    (new SendNewMessageNotificationListener)->handle(
        new MessageSentEvent($chat, $receiver->id)
    );

    $notificationExists = DB::table('notifications')
        ->where('notifiable_id', $receiver->id)
        ->where('notifiable_type', $receiver->getMorphClass())
        ->where('type', NewMesssageNotification::class)
        ->exists();

    expect($notificationExists)->toBeTrue();
});

it('queues the new message notification listener after the message sent event', function () {
    Queue::fake();

    $sender = createUser();
    $receiver = createUser();

    actingAsApi($sender)
        ->postJson("/api/chat/send-chat/{$receiver->id}", [
            'message' => 'Queued notification',
        ])
        ->assertCreated();

    Queue::assertPushed(
        CallQueuedListener::class,
        function ($job) {
            return $job->class === SendNewMessageNotificationListener::class;
        }
    );
});
