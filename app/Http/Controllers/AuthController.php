<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\ActiveDeviceService;
use App\Services\LoginService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginService $service)
    {
        $result = $service->login($request->validated());
        $user = $result['user'];
        $token = $result['token'];

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
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
        return (new UserController())->store($request);
    }

    public function logout(Request $request, ActiveDeviceService $activeDevices)
    {
        $token = $request->user()?->currentAccessToken();
        $activeDevices->release($request->user(), $token?->id);
        $token?->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
}
