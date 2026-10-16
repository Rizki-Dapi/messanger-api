<?php

namespace App\Services;

use App\Models\Friend;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Repositories\Interfaces\StoryRepositoryInterface;
use App\Repositories\Interfaces\StoryViewRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;

class StoryService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly StoryRepositoryInterface $storyRepo,
        private readonly StoryViewRepositoryInterface $storyViewRepo,
    ) {}

    public function create(array $data, User $user): Story
    {
        $media = $data['media'] ?? null;

        $this->getStoryDuration($media);

        $data['expires_at'] = now()->addHours(24);
        $data['user_id'] = $user->id;

        if ($media instanceof UploadedFile) {
            $data['media'] = $this->storeMedia($media);
        }

        return $this->storyRepo->create($data);
    }

    public function getMyStories(User $user): Collection
    {
        $story = $this->storyRepo->getActiveStoriesByUserId($user->id);

        return $story->load(['user', 'views'])->loadCount('views');
    }

    public function getStoryFeed(User $user): Collection
    {
        return $this->storyRepo->getStoryFeed($user->id);
    }

    public function getUserStories(int $userId, User $user): Collection
    {
        $stories = $this->storyRepo->getActiveStoriesByUserId($userId);

        if ($stories->isEmpty()) {
            throw new ModelNotFoundException('No active stories found.');
        }

        $owner = $stories->first()->user;

        if ($owner->id !== $user->id) {
            $this->ensureCanView($stories->first(), $user->id);
        }

        return $stories
            ->load(['user'])
            ->loadCount('views');
    }

    public function viewStory(int $storyId, User $user): StoryView
    {
        $story = $this->storyRepo->findById($storyId);

        if (! $story || $story->expires_at->isPast()) {
            throw new ModelNotFoundException('Story not found or story already expires');
        }

        if ($story->user_id === $user->id) {
            $view = new StoryView([
                'story_id' => $story->id,
                'user_id' => $user->id,
            ]);

            $view->setRelation('user', $user);
            $view->setRelation('story', $story->load('user')->loadCount('views'));

            return $view;
        }

        $this->ensureCanView($story, $user->id);

        return $this->storyViewRepo->firstOrCreate($storyId, $user->id)
            ->load(['user', 'story.user'])
            ->load([
                'story' => fn ($query) => $query->withCount('views'),
            ]);
    }

    public function getStoryViewers(int $storyId, User $user): Collection
    {
        $story = $this->storyRepo->findById($storyId);

        if (! $story) {
            throw new ModelNotFoundException('story not found');
        }

        Gate::forUser($user)->authorize('viewViewers', $story);

        $view = $this->storyViewRepo->getByStoryId($story->id);

        return $view->load('user');
    }

    public function delete(Story $story, User $user): void
    {
        Gate::forUser($user)->authorize('delete', $story);

        $this->storyRepo->delete($story);
    }

    private function ensureCanView(Story $story, int $userId): void
    {
        if ($story->user_id === $userId) {
            return;
        }

        $isMutualFriend = Friend::mutualOf($userId)
            ->where('friend_id', $story->user_id)
            ->exists();

        if (! $isMutualFriend) {
            throw new AuthorizationException('You are not allowed to view this story.');
        }
    }

    private function storeMedia(UploadedFile $media): string
    {
        $path = $media->store('stories', 'public');

        if ($path === false) {
            throw new RuntimeException('Failed to save story media.');
        }

        return $path;
    }

    private function getVideoDuration(UploadedFile $media): float
    {
        $process = new Process([
            'ffprobe',
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $media->getRealPath(),
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            throw ValidationException::withMessages([
                'media' => 'The video cannot be read.',
            ]);
        }

        $duration = trim($process->getOutput());

        if (! is_numeric($duration)) {
            throw ValidationException::withMessages([
                'media' => 'Invalid video duration',
            ]);
        }

        return (float) $duration;
    }

    private function getStoryDuration(?UploadedFile $media): int
    {
        if ($media === null || ! in_array(
            $media->getMimeType(),
            [
                'video/mp4',
                'video/quicktime',
            ]
        )) {
            return 60;
        }

        $duration = $this->getVideoDuration($media);

        if ($duration > 60) {
            throw ValidationException::withMessages([
                'media' => 'The maximum video duration is 60 seconds.',
            ]);
        }

        return (int) ceil($duration);
    }
}
