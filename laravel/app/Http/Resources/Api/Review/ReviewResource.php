<?php

namespace App\Http\Resources\Api\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer     = $this->resource->relationLoaded('customer') ? $this->customer : null;
        $customerName = null;

        if ($customer !== null) {
            $customerName = trim($customer->first_name . ' ' . $customer->last_name) ?: null;
        }

        return [
            'id'            => $this->id,
            'rating'        => $this->rating,
            'comment'       => $this->comment,
            'customer_name' => $customerName,
            'customer_id'   => $customer?->id,
            'status'        => $this->status,
            'created_at'    => $this->created_at,
        ];
    }
}
