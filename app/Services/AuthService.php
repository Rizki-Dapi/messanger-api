<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialException;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\InvalidResetTokenException;
use App\Models\User;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly LogRepositoryInterface $logRepo,
    ) {}

    public function register(array $data): array
    {
        $user = $this->userRepo->create($data);

        $verificationToken = Str::random(64);

        $this->sendRegistrationOtp($user, $verificationToken);

        return [
            'status' => 'verification_required',
            'message' => 'Registration OTP sent. Please verify your email.',
            'verification_token' => $verificationToken,
        ];
    }

    public function login(array $credentials): array
    {
        $identity = $credentials['identity'];

        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        } elseif (preg_match('/^\+?[0-9]{8,15}$/', $identity)) {
            $field = 'phone';
        } else {
            $field = 'name';
        }

        $user = $this->userRepo->findByField($field, $identity);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialException;
        }

        if (Hash::needsRehash($user->password)) {
            $this->userRepo->update($user, [
                'password' => $credentials['password'],
            ]);
        }

        $deviceToken = $credentials['device_token'] ?? null;

        if ($deviceToken) {
            $cacheKey = 'trusted_device:'.$user->id.':'.hash('sha256', $deviceToken);

            if (Cache::get($cacheKey)) {
                return $this->authenticatedLogin($user);
            }
        }

        $cooldownKey = 'login_otp_cooldown:'.$user->id;

        if (Cache::has($cooldownKey)) {
            throw new InvalidCredentialException(
                'Please wait before requesting another OTP.'
            );
        }

        $verificationToken = Str::random(64);

        $this->sendLoginOtp($user, $verificationToken);

        return [
            'status' => 'verification_required',
            'message' => 'OTP verification required. Check your email.',
            'verification_token' => $verificationToken,
        ];
    }

    public function verifyLoginOtp(string $verificationToken, string $otp): array
    {
        $verificationKey = 'login_verification:'.hash('sha256', $verificationToken);
        $verification = Cache::get($verificationKey);

        if (! $verification) {
            throw new InvalidOtpException;
        }

        if ($verification['attempts'] >= 5) {
            Cache::forget($verificationKey);

            throw new InvalidCredentialException;
        }

        if (! Hash::check($otp, $verification['otp_hash'])) {
            $verification['attempts']++;

            if ($verification['attempts'] >= 5) {
                Cache::forget($verificationKey);
            } else {
                Cache::put(
                    $verificationKey,
                    $verification,
                    now()->addMinutes(5)
                );
            }

            throw new InvalidCredentialException;
        }

        $user = $this->userRepo->findById($verification['user_id']);

        if (! $user) {
            Cache::forget($verificationKey);

            throw new InvalidCredentialException;
        }

        Cache::forget($verificationKey);
        Cache::forget('login_otp_cooldown:'.$user->id);

        $deviceToken = Str::random(64);

        Cache::put(
            'trusted_device:'.$user->id.':'.hash('sha256', $deviceToken),
            true,
            now()->addDays(30)
        );

        $result = $this->authenticatedLogin($user);
        $result['device_token'] = $deviceToken;

        return $result;
    }

    public function logout(): void
    {
        $userId = auth('api')->id();

        JWTAuth::invalidate(JWTAuth::getToken());

        $this->logRepo->record([
            'user_id' => $userId,
            'action' => 'user.logout',
        ]);
    }

    public function me(): ?User
    {
        return auth('api')->user();
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
        } catch (JWTException $e) {
            throw new InvalidCredentialException('Your session has expired. please log in again.');
        }

        $user = JWTAuth::setToken($newToken)->authenticate();

        $this->logRepo->record([
            'user_id' => $user->id,
            'action' => 'user.token_refreshed',
        ]);

        return ['user' => $user, 'token' => $newToken];
    }

    public function verifyEmail(int $userId, string $hash)
    {
        $user = $this->userRepo->findById($userId);

        if (! $user || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return ['user' => $user, 'already_verified' => false];
        }

        if ($user->hasVerifiedEmail()) {
            return ['user' => $user, 'already_verified' => true];
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        $this->logRepo->record([
            'user_id' => $user->id,
            'action' => 'user.email_verified',
        ]);

        return ['user' => $user, 'already_verified' => false];
    }

    public function resendVerification(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }

    public function sendPasswordResetLink(string $email): void
    {
        Password::ResetLinkSent(['emal' => $email]);
    }

    public function resetPassword(array $data)
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new InvalidResetTokenException;
        }

        $this->logRepo->record([
            'action' => 'user.Password_reset',
            'context' => ['email' => $data['email']],
        ]);
    }

    public function deleteAccount(User $user): void
    {
        $userId = $user->id;

        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Throwable $e) {
        }

        $this->userRepo->delete($user);

        $this->logRepo->record([
            'user_id' => $userId,
            'action' => 'user.delete_account',
        ]);
    }

    private function sendLoginOtp(User $user, string $verificationToken): void
    {
        $otp = (string) random_int(100000, 999999);

        Mail::raw(
            "Your login OTP code is: {$otp}. This code is valid for 5 minutes.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('OTP code login');
            }
        );

        Cache::put(
            'login_verification:'.hash('sha256', $verificationToken),
            [
                'user_id' => $user->id,
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
            ],
            now()->addMinutes(5)
        );

        Cache::put(
            'login_otp_cooldown:'.$user->id,
            true,
            now()->addSeconds(60)
        );
    }

    private function sendRegistrationOtp(User $user, string $verificationToken): void
    {
        $otp = (string) random_int(100000, 999999);

        Mail::raw(
            "Your registration OTP code: {$otp}. This code is valid for 5 minutes.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('OTP code Registration');
            }
        );

        Cache::put(
            'registration_verification:'.hash('sha256', $verificationToken),
            [
                'user_id' => $user->id,
                'otp_hash' => Hash::make($otp),
                'attempt' => 0.,
            ],
            now()->addMinutes(5)
        );
    }

    private function authenticatedLogin(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        $this->logRepo->record([
            'user_id' => $user->id,
            'action' => 'user.login',
        ]);

        return [
            'status' => 'authenticated',
            'user' => $user,
            'token' => $token,
        ];
    }
}
