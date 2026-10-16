<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AlreadyFriendException extends Exception
{
    public function __construct($message = 'Your already friend with this user.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            [
                'error' => 'conflict',
                'message' => $this->getMessage(),
            ],
            Response::HTTP_CONFLICT
        );
    }
}
