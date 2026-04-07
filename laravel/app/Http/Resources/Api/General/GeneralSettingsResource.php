<?php

namespace App\Http\Resources\Api\General;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GeneralSettingsResource extends JsonResource
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
            'site_title' => $this->resource->site_title[$lang] ?? $this->resource->site_title[app()->getLocale()] ?? null,
            'meta_title' => $this->resource->meta_title[$lang] ?? $this->resource->meta_title[app()->getLocale()] ?? null,
            'meta_description' => $this->resource->meta_description[$lang] ?? $this->resource->meta_description[app()->getLocale()] ?? null,
            'meta_keywords' => $this->resource->meta_keywords[$lang] ?? $this->resource->meta_keywords[app()->getLocale()] ?? null,
            'is_open' => (bool) $this->resource->is_open,
            'logo' => $this->resolveMediaUrl('logo'),
            'favicon' => $this->resolveMediaUrl('favicon'),
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

    private function resolveMediaUrl(string $field): ?string
    {
        $value = $this->resource->{$field} ?? null;

        return $value ? Storage::disk('public')->url($value) : null;
    }
}
