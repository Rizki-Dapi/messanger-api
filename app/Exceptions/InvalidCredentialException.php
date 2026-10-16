<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class InvalidCredentialException extends Exception
{
    public function __construct($message = 'Invalid email/phone/username or password.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Invalid Credentials',
            'message' => $this->getMessage(),
        ], 401);
    }
}
