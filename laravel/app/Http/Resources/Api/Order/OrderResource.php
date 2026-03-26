<?php

namespace App\Http\Resources\Api\Order;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service  = new CurrencyService();
        $currency = $request->attributes->get('currency');

        $items = $this->resource->relationLoaded('items')
            ? $this->items
            : collect();

        $addresses = $this->resource->relationLoaded('addresses')
            ? $this->addresses
            : collect();

        return [
            'id'               => $this->id,
            'status'           => $this->status,
            'total_amount'     => $service->convertPrice($this->total_amount, $currency),
            'items'            => OrderItemResource::collection($items),
            'billing_address'  => $this->resolveBillingAddress($addresses, $request),
            'shipping_address' => $this->resolveShippingAddress($addresses, $request),
            'created_at'       => $this->created_at,
        ];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function resolveBillingAddress($addresses, Request $request): ?OrderAddressResource
    {
        $address = $addresses->firstWhere('type', 'billing');
        return $address ? new OrderAddressResource($address) : null;
    }

    private function resolveShippingAddress($addresses, Request $request): ?OrderAddressResource
    {
        $address = $addresses->firstWhere('type', 'shipping');
        return $address ? new OrderAddressResource($address) : null;
    }
}
