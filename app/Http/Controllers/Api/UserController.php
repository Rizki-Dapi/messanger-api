<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNameRequest;
use App\Http\Requests\UpdatePhotoProfileRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function updateName(UpdateNameRequest $req): JsonResponse
    {
        $data = $req->validated();

        $result = $this->userService->updateName($req->user(), $data['name']);

        return ResponseHelper::success(
            message: 'updated name successfully',
            data: ['name' => $result->name]
        );
    }

    public function updatePhotoProfile(UpdatePhotoProfileRequest $req): JsonResponse
    {
        $data = $req->validated();

        $result = $this->userService->updateProfilePhoto($data['profile_photo_path'], $req->user());

        return ResponseHelper::success(
            message: 'updated photo profile successfully',
            data: ['photo_profile' => $result->profile_photo_path],
        );
    }

    public function deletePhotoProfile(Request $req): JsonResponse
    {
        $this->userService->deleteProfilePhoto($req->user());

        return ResponseHelper::success(message: 'delete photo profile successfully');
    }
}
