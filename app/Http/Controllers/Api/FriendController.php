<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\FriendResource;
use App\Models\Friend;
use App\Services\FriendService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class FriendController extends Controller
{
    public function __construct(private readonly FriendService $friendService) {}

    public function indexFriendList(Request $req): JsonResponse
    {
        $result = $this->friendService->getFriends($req->user());

        return ResponseHelper::success(
            message: 'friend list retrieved successfully',
            data: FriendResource::collection($result),
        );
    }

    public function showFriend(Friend $friend, Request $req): JsonResponse
    {
        $result = $this->friendService->findFriendById($friend, $req->user());

        return ResponseHelper::success(
            message: 'friend retrieved successfully',
            data: new FriendResource($result),
        );
    }

    public function addFriend(int $friendId, Request $req): JsonResponse
    {
        $result = $this->friendService->addFriend($friendId, $req->user());

        return ResponseHelper::success(
            message: 'add friend succesffully',
            data: [
                'user_id' => $result->user_id,
                'friend_id' => $result->friend_id,
            ],
            status: 201
        );
    }

    public function unFriend(Friend $friend, Request $req): JsonResponse
    {
        $this->friendService->unFriend($friend, $req->user());

        return ResponseHelper::success(message: 'unFriend successfully');
    }
}
