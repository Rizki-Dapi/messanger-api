<?php

namespace App\Http\Middleware;

use App\Models\SystemLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $this->unauthorizedResponse('Unauthorized: Token not provided');
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return $this->unauthorizedResponse('Unauthorized: Invalid user', $request, log: true);
            }
        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Unauthorized: Token has expired', $request, log: true);
        } catch (TokenBlacklistedException $e) {
            return $this->unauthorizedResponse('Unauthorized: Token has been blacklisted', $request, log: true);
        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Unauthorized: Token is invalid', $request, log: true);
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Unauthorized: Could not parse token', $request, log: true);
        }

        return $next($request);
    }

    private function unauthorizedResponse(string $message, ?Request $request = null, bool $log = false): Response
    {
        if ($log && $request) {
            try {
                SystemLog::create([
                    'level' => 'warning',
                    'message' => $message,
                    'context' => [
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl(),
                        'user_agent' => $request->header('User-Agent'),
                    ],
                    'logged_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['error' => $message], Response::HTTP_UNAUTHORIZED);
    }
}
