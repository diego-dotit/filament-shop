<?php

namespace App\Http\Resources\Api\Product;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = new CurrencyService();
        $currency = $request->attributes->get('currency');

        return [
            'id'             => $this->id,
            'sku'            => $this->sku,
            'regular_price'  => $service->convertPrice($this->regular_price, $currency),
            'special_price'  => $service->convertPrice($this->special_price, $currency),
            'stock_quantity' => $this->stock_quantity,
            'weight'         => $this->weight,
            'is_active'      => $this->is_active,
            'attributes'     => AttributeResource::collection(
                $this->resource->relationLoaded('attributes')
                    ? $this->attributes
                    : collect()
            ),
        ];
    }
}
