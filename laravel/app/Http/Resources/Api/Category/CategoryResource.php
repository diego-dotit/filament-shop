<?php

namespace App\Http\Resources\Api\Category;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $this->resolveLang($request);

        return [
            'id'       => $this->id,
            'slug'     => $this->getSlugForLocale($lang)?->slug ?? $this->slug,
            'name'     => $this->getTranslation('name', $lang),
            'is_active' => $this->is_active,
            'children' => static::collection(
                $this->resource->relationLoaded('children')
                    ? $this->children
                    : collect()
            ),
            'image'    => $this->resolveImage(),
            'parent'   => $this->when(
                $this->resource->relationLoaded('parent') && $this->parent !== null,
                fn () => [
                    'id'   => $this->parent->id,
                    'slug' => $this->parent->getSlugForLocale($lang)?->slug ?? $this->parent->slug,
                    'name' => $this->parent->getTranslation('name', $lang),
                ]
            ),
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

    private function resolveImage(): ?string
    {
        if (method_exists($this->resource, 'getFirstMediaUrl')) {
            try {
                $url = $this->resource->getFirstMediaUrl();
                return $url ?: null;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
