<?php

namespace App\Http\Resources\Api\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Handles both ProductAttribute (with attribute relationship) and
     * ProductVariantAttribute (with name/value fields directly).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // ProductVariantAttribute has name/value directly
        // ProductAttribute has value + attribute relationship with name
        $name = $this->name
            ?? $this->whenLoaded('attribute', fn () => $this->attribute?->name);

        return [
            'name'  => $name,
            'value' => $this->value,
        ];
    }
}
