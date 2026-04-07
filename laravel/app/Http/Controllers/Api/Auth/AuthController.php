<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        return ApiResponse::success(['id' => $user->id, 'name' => $user->name, 'email' => $user->email], 201, 'Registration successful.');
    }

    /**
     * Authenticate a user and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return ApiResponse::error('invalid_credentials', 'Invalid credentials.', 401);
        }

        /** @var User $user */
        $user  = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success(['id' => $user->id, 'name' => $user->name, 'email' => $user->email], 200, null, ['token' => $token]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return ApiResponse::success(['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    }
}
