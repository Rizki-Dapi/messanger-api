<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', fn (User $user, int $id) => $user->id === $id);

Broadcast::channel('conversation.{conversationId}', function (User $user, Conversation $conversation) {
    return $conversation->hasParticipant($user)
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});
