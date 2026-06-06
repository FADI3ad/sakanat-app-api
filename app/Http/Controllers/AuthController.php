<?php

namespace App\Http\Controllers;

use App\Services\LoginService;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function login(Request $request , LoginService $service)
    {

        $data['email'] = $request->email;
        $data['password'] = $request->password;

        return $service->login($data);
    }

    public function register(Request $request)
    {
        // Handle registration logic here
    }

    public function logout(Request $request)
    {
        // Handle logout logic here
    }
}
