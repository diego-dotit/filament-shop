<?php

namespace App\Http\Resources\Api\Order;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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

        return [
            'product_name_snapshot' => $this->product_name_snapshot,
            'variant_sku_snapshot'  => $this->variant_sku_snapshot,
            'unit_price_snapshot'   => $service->convertPrice($this->unit_price_snapshot, $currency),
            'quantity'              => $this->quantity,
            'line_total_snapshot'   => $service->convertPrice($this->line_total_snapshot, $currency),
        ];
    }
}
