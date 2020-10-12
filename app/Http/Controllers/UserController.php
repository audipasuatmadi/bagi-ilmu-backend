<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request) {
        $request->validate([
            'name' => ['required', 'string'],
            'username' => ['required', 'string', 'unique:users'],
            'password' => ['required', 'string']
        ]);
        
        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request['password'])
        ]);

        return $this->login($request);
    }

    public function login(Request $request) {
        if (Auth::attempt($request->only('username', 'password'))) {
            return response()->json(Auth::user());
        } else {
            return response('user not found', 401);
        }
    }

    public function logout() {
        Auth::logout();
    }
}
