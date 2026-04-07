<?php

namespace App\Http\Resources\Api\Manufacturer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManufacturerResource extends JsonResource
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
            'id'               => $this->id,
            'slug'             => ($this->exists ? $this->getSlugForLocale($lang)?->slug : null) ?? $this->slug,
            'name'             => $this->getTranslation('name', $lang),
            'description'      => $this->getTranslation('description', $lang),
            'meta_title'       => $this->getTranslation('meta_title', $lang),
            'meta_description' => $this->getTranslation('meta_description', $lang),
            'meta_keywords'    => $this->getTranslation('meta_keywords', $lang),
            'image'            => $this->resolveImage(),
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
                $url = $this->resource->getFirstMediaUrl('thumbnail');

                return $url ?: null;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
