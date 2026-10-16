<?php

namespace App\Listeners;

use App\Events\MessageSentEvent;
use App\Models\User;
use App\Notifications\NewMesssageNotification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewMessageNotificationListener implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSentEvent $event): void
    {
        User::find($event->receiverId)
            ->notify(new NewMesssageNotification($event->message));
    }
}
