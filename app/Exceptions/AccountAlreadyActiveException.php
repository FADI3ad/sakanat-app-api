<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class AccountAlreadyActiveException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ACCOUNT_ALREADY_ACTIVE',
            'message' => 'This account is already logged in on another device. Please contact the developer or administrator to remove the active device.',
        ], 409);
    }
}
