<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCredentialsException extends RuntimeException
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'INVALID_CREDENTIALS',
            'message' => 'The provided credentials are invalid.',
        ], 401);
    }
}