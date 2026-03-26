<?php

namespace App\Http\Resources\Api\Cart;

use App\Http\Resources\Api\Product\ProductResource;
use App\Http\Resources\Api\Product\ProductVariantResource;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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

        $basePrice  = (float) ($this->productVariant?->regular_price ?? 0);
        $lineTotal  = $this->quantity * $basePrice;

        return [
            'id'         => $this->id,
            'product'    => $this->resource->relationLoaded('product')
                ? new ProductResource($this->product)
                : null,
            'variant'    => $this->resource->relationLoaded('productVariant')
                ? new ProductVariantResource($this->productVariant)
                : null,
            'quantity'   => $this->quantity,
            'line_total' => $service->convertPrice((string) $lineTotal, $currency),
        ];
    }
}
