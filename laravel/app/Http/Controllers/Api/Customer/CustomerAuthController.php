<?php

namespace App\Http\Controllers\Api\Customer;

use App\Domains\Customer\Models\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\CustomerLoginRequest;
use App\Http\Requests\Api\Customer\CustomerRegisterRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    /**
     * Register a new customer and issue a Sanctum token.
     */
    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $customer = Customer::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $customer->createToken('api')->plainTextToken;

        return ApiResponse::success(new CustomerResource($customer), 201, 'Registration successful.', ['token' => $token]);
    }

    /**
     * Authenticate a customer and issue a Sanctum token.
     */
    public function login(CustomerLoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('customers')->attempt($credentials)) {
            return ApiResponse::error('invalid_credentials', 'Invalid credentials.', 401);
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customers')->user();
        $token = $customer->createToken('api')->plainTextToken;

        return ApiResponse::success(new CustomerResource($customer), 200, null, ['token' => $token]);
    }

    /**
     * Return the authenticated customer's profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        return ApiResponse::success(new CustomerResource($customer));
    }
}
