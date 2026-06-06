<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginService {

    
    public function login(array $data){


        $user  = User::where('email' , '=' , $data['email'])->first();

        if(!$user  || !Hash::check($data['password'], $user->password)){
            throw new Exception('Invalid credentials');
        }
        
    
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'id'=>$user->id,
            'name'=>$user->name,
            'type'=>$user->type,
            'token'=>$token
        ]);

    }
} 
