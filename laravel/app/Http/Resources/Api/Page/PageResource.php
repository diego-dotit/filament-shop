<?php

namespace App\Http\Resources\Api\Page;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
            'id' => $this->id,
            'title' => $this->getTranslation('title', $lang),
            'description' => $this->getTranslation('description', $lang),
            'meta_title' => $this->getTranslation('meta_title', $lang),
            'meta_description' => $this->getTranslation('meta_description', $lang),
            'meta_keywords' => $this->getTranslation('meta_keywords', $lang),
            'slug' => ($this->exists ? $this->getSlugForLocale($lang)?->slug : null) ?? $this->slug,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
}
