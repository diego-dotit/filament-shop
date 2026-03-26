<?php

namespace App\Http\Resources\Api\Cart;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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

        $rawTotal = $items->sum(function ($item) {
            $price = (float) ($item->productVariant?->regular_price ?? 0);
            return $item->quantity * $price;
        });

        return [
            'id'    => $this->id,
            'items' => CartItemResource::collection($items),
            'total' => $service->convertPrice((string) $rawTotal, $currency),
        ];
    }
}
