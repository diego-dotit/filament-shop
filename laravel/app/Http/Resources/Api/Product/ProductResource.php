<?php

namespace App\Http\Resources\Api\Product;

use App\Http\Resources\Api\Category\CategoryResource;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang     = $this->resolveLang($request);
        $service  = new CurrencyService();
        $currency = $request->attributes->get('currency');

        return [
            'id'                 => $this->id,
            'slug'               => $this->slug,
            'name'               => $this->getTranslation('name', $lang),
            'description'        => $this->getTranslation('description', $lang),
            'is_active'          => $this->is_active,
            'price'              => $this->resolveLowestPrice(),
            'variants'           => ProductVariantResource::collection(
                $this->resource->relationLoaded('variants')
                    ? $this->variants
                    : collect()
            ),
            'images'             => $this->resolveImages(),
            'product_attributes' => $this->resolveProductAttributes($request),
            'categories'         => CategoryResource::collection(
                $this->resource->relationLoaded('categories')
                    ? $this->categories
                    : collect()
            ),
            'manufacturers'      => $this->resource->relationLoaded('manufacturers')
                ? $this->manufacturers->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])
                : [],
        ];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function resolveLang(Request $request): string
    {
        $lang = $request->attributes->get('lang');

        if ($lang && isset($lang->code)) {
            return $lang->code;
        }

        return app()->getLocale();
    }

    private function resolveLowestPrice(): ?string
    {
        if (! $this->resource->relationLoaded('variants')) {
            return null;
        }

        $lowest = $this->variants
            ->where('is_active', true)
            ->min('regular_price');

        return $lowest !== null ? (string) $lowest : null;
    }

    private function resolveImages(): array
    {
        if (method_exists($this->resource, 'getMedia')) {
            try {
                return $this->resource->getMedia('images')
                    ->map(fn ($media) => $media->getUrl())
                    ->values()
                    ->toArray();
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }

    private function resolveProductAttributes(Request $request): array
    {
        if (! $this->resource->relationLoaded('productAttributes')) {
            return [];
        }

        return $this->productAttributes->map(function ($attr) {
            return [
                'name'  => $attr->relationLoaded('attribute') ? $attr->attribute?->name : null,
                'value' => $attr->value,
            ];
        })->toArray();
    }
}
