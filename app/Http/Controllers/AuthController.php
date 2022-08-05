<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register']]);
    }


    public function register(Request $request): JsonResponse
    {
        $validate  = $this->validateInput($request);

        $validate['password'] = Hash::make($validate['password']);

        User::query()->create($validate);

        return response()->json([
            'message' => 'Регистрация прошла успешно',
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $credentials = $request->only('username', 'password');
        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'message' => 'Неверные логин или пароль',
            ], 401);
        }

        return response()->json([
            'message' => 'Успешная авторизация',
            'auth' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);

    }

    public function logout(): JsonResponse
    {
        Auth::logout();
        return response()->json([
            'message' => 'Успешный выход из системы',
        ]);
    }

    public function validateInput($request)
    {
        return $request->validate([
            'username' => 'required|string|max:255|unique:users|regex:/^\d*[a-zA-Z][a-zA-Z\d]*$/',
            'password' => 'required|string|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
        ]);
    }

}
