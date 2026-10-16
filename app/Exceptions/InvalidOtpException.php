<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InvalidOtpException extends Exception
{
    public function __construct($message = 'OTP has expired. Please request a new OTP.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            [
                'status' => 'Unauthorized',
                'message' => $this->getMessage(),
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
