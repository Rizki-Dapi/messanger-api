<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InvalidResetTokenException extends Exception
{
    public function __construct($message = 'This password reset token is invalid or has expired.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            [
                'error' => 'unauthorized',
                'message' => $this->getMessage(),
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
