<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoryRequest;
use App\Http\Resources\StoryResource;
use App\Http\Resources\ViewStoryResource;
use App\Models\Story;
use App\Services\StoryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class StoryController extends Controller
{
    public function __construct(private readonly StoryService $storyService) {}

    public function indexMyStory(Request $req): JsonResponse
    {
        $result = $this->storyService->getMyStories($req->user());

        return ResponseHelper::success(
            message: 'Stories retrieved successfully',
            data: StoryResource::collection($result),
        );
    }

    public function indexStoryFeed(Request $req): JsonResponse
    {
        $result = $this->storyService->getStoryFeed($req->user());

        return ResponseHelper::success(
            message: 'Story feeds retrieved successfully',
            data: StoryResource::collection($result),
        );
    }

    public function indexStoryUser(int $userId, Request $req): JsonResponse
    {
        $result = $this->storyService->getUserStories($userId, $req->user());

        return ResponseHelper::success(
            message: 'view story user retrieved successfully',
            data: ViewStoryResource::collection($result),
        );
    }

    public function viewStory(int $storyId, Request $req): JsonResponse
    {
        $result = $this->storyService->viewStory($storyId, $req->user());

        return ResponseHelper::success(
            message: 'view story successfully',
            data: new ViewStoryResource($result),
        );
    }

    public function indexStoryViewers(int $storyId, Request $req): JsonResponse
    {
        $result = $this->storyService->getStoryViewers($storyId, $req->user());

        return ResponseHelper::success(
            message: 'get story viewers successfully',
            data: ViewStoryResource::collection($result),
        );
    }

    public function store(StoreStoryRequest $req): JsonResponse
    {
        $result = $this->storyService->create($req->validated(), $req->user());

        return ResponseHelper::success(
            message: 'Story created successfully',
            data: new StoryResource($result),
            status: 201
        );
    }

    public function destroy(Request $req, Story $story): JsonResponse
    {
        $this->storyService->delete($story, $req->user());

        return ResponseHelper::success(message: 'story delete successfully');
    }
}
