<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyLoginOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $req): JsonResponse
    {
        $result = $this->authService->register($req->validated());

        return ResponseHelper::success(
            message: $result['message'],
            data: [
                'status' => $result['status'],
                'verification_token' => $result['verification_token'],
            ],
            status: 201,
        );
    }

    public function login(LoginRequest $req): JsonResponse
    {
        $result = $this->authService->login($req->validated());

        if ($result['status'] === 'verification_required') {
            return ResponseHelper::success(
                message: $result['message'],
                data: [
                    'status' => $result['status'],
                    'verification_token' => $result['verification_token'],
                ],
            );
        }

        return ResponseHelper::success(
            message: 'login successfully',
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type' => 'bearer',
            ],
        );
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $req): JsonResponse
    {
        $data = $req->validated();

        $result = $this->authService->verifyLoginOtp(
            $data['verification_token'],
            $data['otp']
        );

        return ResponseHelper::success(
            message: 'login successfully',
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type' => 'bearer',
                'device_token' => $result['device_token'],
            ]
        );
    }

    public function logout()
    {
        $this->authService->logout();

        return ResponseHelper::success(message: 'logout successfully');
    }

    public function me()
    {
        $user = $this->authService->me();

        return ResponseHelper::success(
            message: 'User rerieved successfully',
            data: ['user' => new UserResource($user)],
        );
    }

    public function refreshToken()
    {
        $result = $this->authService->refresh();

        return ResponseHelper::success(
            message: 'Token refreshed successfully',
            data: [
                'user' => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type' => 'bearer',
            ],
        );
    }

    public function deleteAccount(Request $req)
    {
        $this->authService->deleteAccount($req->validated());

        return ResponseHelper::success(message: 'delete account successfully');
    }

    public function verifyEmail(int $id, string $hash)
    {
        $result = $this->authService->verifyEmail($id, $hash);

        if (! $result['user']) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Invalid verification link',
            ], Response::HTTP_FORBIDDEN);
        }

        $message = $result['already_verified']
            ? 'Email already verified'
            : 'Email verified successfully';

        return ResponseHelper::success(message: $message);
    }

    public function resendVerification(Request $req)
    {
        $sent = $this->authService->resendVerification($req->user());

        $message = $sent
            ? 'Verification link sent'
            : 'Email already verified';

        return ResponseHelper::success(message: $message);
    }

    public function forgetPassword(ForgetPasswordRequest $req)
    {
        $this->authService->sendPasswordResetLink($req->validated());

        return ResponseHelper::success(message: 'send link password reset successfully');
    }

    public function resetPassword(ResetPasswordRequest $req)
    {
        $this->authService->resetPassword($req->validated());

        return ResponseHelper::success(message: 'updated password successfully');
    }
}
