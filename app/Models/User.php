<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password', 'phone', 'profile_photo_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function sentConversations()
    {
        return $this->hasMany(Conversation::class, 'sender_id');
    }

    public function receivedConversations()
    {
        return $this->hasMany(Conversation::class, 'receiver_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Chat::class, 'sender_id');
    }

    public function friends()
    {
        return $this->hasMany(Friend::class, 'user_id');
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function storyViews()
    {
        return $this->hasMany(StoryView::class);
    }
}
