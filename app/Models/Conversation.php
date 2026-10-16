<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function messages()
    {
        return $this->hasMany(Chat::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Chat::class)->latestOfMany();
    }

    public function hasParticipant(User $user): bool
    {
        return in_array($user->id, [$this->sender_id, $this->receiver_id]);
    }

    public function otherParticipantId(User $user): int
    {
        return $this->sender_id === $user->id ? $this->receiver_id : $this->sender_id;
    }
}
