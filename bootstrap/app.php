<?php

use App\Http\Middleware\ApiHeaderMiddleware;
use App\Http\Middleware\JWTMiddleware;
use App\Models\SystemLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/broadcasting.php',
        ['prefix' => 'api', 'middleware' => ['api', 'jwt.auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            ApiHeaderMiddleware::class,
        ]);

        $middleware->alias([
            'jwt.auth' => JWTMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $logToMongo = function (string $level, string $message, Throwable $e) {
            try {
                SystemLog::create([
                    'level' => $level,
                    'message' => $message,
                    'context' => [
                        'exception' => get_class($e),
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                    'logged_at' => Carbon::now(),
                ]);
            } catch (Throwable $ignored) {
            }
        };

        $exceptions->render(function (Throwable $exception, $request) use ($logToMongo) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'error' => 'Validation Error',
                    'message' => $exception->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'Resource not found',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'error' => 'Method Not Allowed',
                    'message' => 'This HTTP method is not allowed for the requested route',
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            if ($exception instanceof QueryException) {
                $logToMongo('error', 'Database Error', $exception);

                return response()->json([
                    'error' => 'Internal Server Error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();

                if ($statusCode >= 500) {
                    $logToMongo('error', 'HTTP Exception', $exception);
                }

                return response()->json([
                    'error' => Response::$statusTexts[$statusCode] ?? 'Error',
                    'message' => $exception->getMessage() ?: (Response::$statusTexts[$statusCode] ?? 'An error occurred'),
                ], $statusCode);
            }

            $logToMongo('error', 'Unhandled Exception', $exception);

            return response()->json([
                'error' => 'Internal Server Error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
