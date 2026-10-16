<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'user_id',
        'captions',
        'media',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $with = ['user', 'views'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    public function prunable()
    {
        return static::where('expires_at', '<=', now());
    }

    protected function pruning()
    {
        if ($this->media) {
            Storage::disk('public')->delete($this->media);
        }
    }
}
