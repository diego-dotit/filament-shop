<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domains\Customer\Models\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Register a new user and linked customer record.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Split the single "name" field into first / last name.
        $nameParts = explode(' ', trim($data['name']), 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';

        $customer = DB::transaction(function () use ($data, $firstName, $lastName): Customer {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ]);

            return $user->customer()->create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $data['email'],
            ]);
        });

        return ApiResponse::success(new CustomerResource($customer), 201, 'Registration successful.');
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

        return ApiResponse::success(new CustomerResource($user->customer), 200, null, ['token' => $token]);
    }

    /**
     * Return the currently authenticated user with customer details.
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->customer) {
            return ApiResponse::error('customer_not_found', 'Customer profile not found.', 404);
        }

        return ApiResponse::success(new CustomerResource($user->customer));
    }
}
