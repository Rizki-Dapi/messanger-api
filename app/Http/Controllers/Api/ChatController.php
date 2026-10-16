<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatRequest;
use App\Http\Requests\UpdateChatRequest;
use App\Http\Resources\ChatResource;
use App\Http\Resources\ConversationResource;
use App\Models\Chat;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function indexChats(int $conversationId, Request $req): JsonResponse
    {
        $result = $this->chatService->getChats($conversationId, $req->user());

        return ResponseHelper::success(
            message: 'chat retrieved successfully',
            data: ChatResource::collection($result),
        );
    }

    public function indexConversations(Request $req): JsonResponse
    {
        $result = $this->chatService->getConversation($req->user());

        return ResponseHelper::success(
            message: 'conversations retrieved successfully',
            data: ConversationResource::collection($result),
        );
    }

    public function sendChat(int $receiverId, StoreChatRequest $req): JsonResponse
    {
        $result = $this->chatService->sendChat($receiverId, $req->validated('message'), $req->user());

        return ResponseHelper::success(
            message: 'send chat successfully',
            data: new ChatResource($result),
            status: 201
        );
    }

    public function sendChatToYourself(StoreChatRequest $req): JsonResponse
    {
        $result = $this->chatService->sendChatToYourself($req->validated('message'), $req->user());

        return ResponseHelper::success(
            message: 'send chat successfully',
            data: new ChatResource($result),
            status: 201
        );
    }

    public function markAsRead(int $conversationId, Request $req): JsonResponse
    {
        $this->chatService->markAsRead($conversationId, $req->user());

        return ResponseHelper::success(message: 'mark chat as read successfully');
    }

    public function getUnreadCount(int $conversationId, Request $req): JsonResponse
    {
        $result = $this->chatService->getUnreadCount($conversationId, $req->user());

        return ResponseHelper::success(
            message: 'unread count retrieved successfully',
            data: $result
        );
    }

    public function update(Chat $chat, UpdateChatRequest $req): JsonResponse
    {
        $result = $this->chatService->update($chat, $req->validated('message'), $req->user());

        return ResponseHelper::success(
            message: 'update message chat succesfully',
            data: ['message' => $result->message]
        );
    }

    public function delete(Chat $chat, Request $req): JsonResponse
    {
        $this->chatService->delete($chat, $req->user());

        return ResponseHelper::success(message: 'deleted message successfully');
    }
}
