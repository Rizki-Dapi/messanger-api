<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'captions' => $this->captions,
            'media' => $this->media,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'photo_profile' => $this->user->profile_photo_path,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'viewers' => $this->whenCounted('views'),
        ];
    }
}
