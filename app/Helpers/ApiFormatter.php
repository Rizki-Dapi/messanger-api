<?php

namespace App\Helpers;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiFormatter
{
    public static function createJson(string $message, mixed $data = []): array
    {
        if ($data instanceof JsonResource) {
            $data = $data->toArray(request());
        }

        return [
            'message' => $message,
            'data' => self::filterSensitiveData(is_array($data) ? $data : []),
        ];
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
