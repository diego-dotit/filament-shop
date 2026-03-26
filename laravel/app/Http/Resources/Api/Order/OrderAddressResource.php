<?php

namespace App\Http\Resources\Api\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type'           => $this->type,
            'country'        => $this->country,
            'city'           => $this->city,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'postcode'       => $this->postcode,
        ];
    }
}
