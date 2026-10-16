<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/verify-otp', [AuthController::class, 'verifyLoginOtp'])->middleware('throttle:6,1');
    Route::post('/forget-password', [AuthController::class, 'forgetPassword'])->middleware('throttle:6,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->middleware('throttle:10,1');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::delete('/account', [AuthController::class, 'deleteAccount']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1');
    });
});

Route::middleware('jwt.auth')->group(function () {
    Route::prefix('/user')->group(function () {
        Route::put('/update-name', [UserController::class, 'updateName']);
        Route::post('/update-photo-profile', [UserController::class, 'updatePhotoProfile']);
        Route::put('/delete-photo-profile', [UserController::class, 'deletePhotoProfile']);
    });

    Route::prefix('/chat')->group(function () {
        Route::get('/index-chats/{conversationId}', [ChatController::class, 'indexChats']);
        Route::get('/index-conversations', [ChatController::class, 'indexConversations']);
        Route::get('/unread-count/{conversationId}', [ChatController::class, 'getUnreadCount']);
        Route::post('/send-chat/{receiverId}', [ChatController::class, 'sendChat']);
        Route::post('/send-chat-to-yourself', [ChatController::class, 'sendChatToYourself']);
        Route::put('/mark-as-read/{conversationId}', [ChatController::class, 'markAsRead']);
        Route::put('/update/{chat}', [ChatController::class, 'update']);
        Route::delete('/{chat}', [ChatController::class, 'delete']);
    });

    Route::prefix('/friend')->group(function () {
        Route::get('/list', [FriendController::class, 'indexFriendList']);
        Route::get('/shwo/{friend}', [FriendController::class, 'showFriend']);
        Route::post('/add/{friendId}', [FriendController::class, 'addFriend']);
        Route::delete('/unfriend/{friend}', [FriendController::class, 'unFriend']);
    });

    Route::prefix('/stories')->group(function () {
        Route::get('/index-my-stories', [StoryController::class, 'indexMyStory']);
        Route::get('/index-feed', [StoryController::class, 'indexStoryFeed']);
        Route::get('/index-user-story/{userId}', [StoryController::class, 'indexStoryUser']);
        Route::get('/view/{storyId}', [StoryController::class, 'viewStory']);
        Route::get('/index-viewers/{storyId}', [StoryController::class, 'indexStoryViewers']);
        Route::post('/', [StoryController::class, 'store']);
        Route::delete('/{story}', [StoryController::class, 'destroy']);
    });
});
