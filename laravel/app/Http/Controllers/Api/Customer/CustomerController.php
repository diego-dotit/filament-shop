<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\UpdateCustomerRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * GET /customers/me
     * Return the authenticated user's customer profile.
     */
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        return ApiResponse::success((new CustomerResource($customer))->toArray($request));
    }

    /**
     * PUT /customers/me
     * Update the authenticated user's customer profile.
     */
    public function update(UpdateCustomerRequest $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $customer->update($request->validated());

        return ApiResponse::success((new CustomerResource($customer))->toArray($request));
    }
}
