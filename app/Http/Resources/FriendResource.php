<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'photo_profile' => $this->user->profile_photo_path,
            ]),
            'friend' => $this->whenLoaded('friend', fn () => [
                'id' => $this->friend->id,
                'name' => $this->friend->name,
                'photo_profile' => $this->friend->profile_photo_path,
            ]),
        ];
    }
}
