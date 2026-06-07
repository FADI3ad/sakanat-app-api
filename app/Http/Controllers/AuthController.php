<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\LoginService;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function login(LoginRequest $request , LoginService $service)
    {
        $result = $service->login($request->validated());
        $user = $result['user'];
        $token = $result['token'];

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => $user->type,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    public function register(Request $request)
    {
        
    }

    public function logout(Request $request)
    {
        // Handle logout logic here
    }
}
