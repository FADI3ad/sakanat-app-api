<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\LoginService;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function login(LoginRequest $request , LoginService $service)
    {
        return $service->login($request->validated());
    }

    public function register(Request $request)
    {
        
    }

    public function logout(Request $request)
    {
        // Handle logout logic here
    }
}
