<?php

namespace App\Http\Controllers\Api\Customer;

use App\Domains\Customer\Models\CustomerAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\StoreAddressRequest;
use App\Http\Requests\Api\Customer\UpdateAddressRequest;
use App\Http\Resources\Api\Customer\CustomerAddressResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * GET /customers/me/addresses
     * Return paginated list of the authenticated customer's addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $customer  = $request->user()->customer;
        $addresses = $customer->addresses()->paginate();

        $data = CustomerAddressResource::collection($addresses)->response()->getData(true);

        return ApiResponse::success($data['data'], extra: ['links' => $data['links'], 'meta' => $data['meta']]);
    }

    /**
     * POST /customers/me/addresses
     * Create a new address for the authenticated customer.
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $address  = $customer->addresses()->create($request->validated());

        return ApiResponse::success((new CustomerAddressResource($address))->toArray($request), 201);
    }

    /**
     * PUT /customers/me/addresses/{id}
     * Update an address that belongs to the authenticated customer.
     */
    public function update(UpdateAddressRequest $request, CustomerAddress $address): JsonResponse
    {
        $customer = $request->user()->customer;

        if ($customer->id !== $address->customer_id) {
            return ApiResponse::error('forbidden', 'Forbidden.', 403);
        }

        $address->update($request->validated());

        return ApiResponse::success((new CustomerAddressResource($address))->toArray($request));
    }

    /**
     * DELETE /customers/me/addresses/{id}
     * Delete an address that belongs to the authenticated customer.
     */
    public function destroy(Request $request, CustomerAddress $address): JsonResponse
    {
        $customer = $request->user()->customer;

        if ($customer->id !== $address->customer_id) {
            return ApiResponse::error('forbidden', 'Forbidden.', 403);
        }

        $address->delete();

        return ApiResponse::success(null, 204);
    }
}
