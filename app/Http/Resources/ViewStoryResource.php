<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViewStoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'story' => $this->whenLoaded('story', fn () => [
                'id' => $this->story->id,
                'captions' => $this->story->captions,
                'media' => $this->story->media,
                'creator' => $this->whenLoaded('story.user', fn () => [
                    'id' => $this->story->user->id,
                    'name' => $this->story->user->name,
                    'photo_profile' => $this->story->user->profile_photo_path,
                ]),
            ]),
            'user_view' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'photo_profile' => $this->user->profile_photo_path,
            ]),
        ];
    }
}
