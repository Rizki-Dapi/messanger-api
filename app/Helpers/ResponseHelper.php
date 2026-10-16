<?php

namespace App\Helpers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseHelper
{
    public static function success(mixed $data = [], string $message = 'Success', int $status = 200): JsonResponse
    {
        if ($data instanceof ResourceCollection || $data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        if (is_array($data)) {
            $data = self::filterSensitiveData($data);
        }

        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function filterSensitiveData(array $data = []): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'api_key',
            'secret',
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::filterSensitiveData($value);

                continue;
            }

            if (in_array($key, $sensitiveFields, true)) {
                $data[$key] = '[FILTERED]';
            }
        }

        return $data;
    }
}
