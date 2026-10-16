<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id',
    ];

    protected $with = ['user', 'friend'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    public function scopeMutualOf(Builder $query, int $userId): Builder
    {
        return $query
            ->where('friends.user_id', $userId)
            ->whereExists(
                fn ($subquery) => $subquery
                    ->selectRaw('1')
                    ->from('friends as r')
                    ->whereColumn('r.user_id', 'friends.friend_id')
                    ->where('r.friend_id', $userId)
            );
    }
}
