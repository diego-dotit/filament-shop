<?php

namespace App\Http\Resources\Api\Blog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralBlogSettingsResource extends JsonResource
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
            'blog_title'       => $this->resource->blog_title[$lang] ?? $this->resource->blog_title[app()->getLocale()] ?? null,
            'blog_description' => $this->resource->blog_description[$lang] ?? $this->resource->blog_description[app()->getLocale()] ?? null,
            'meta_title'       => $this->resource->meta_title[$lang] ?? $this->resource->meta_title[app()->getLocale()] ?? null,
            'meta_description' => $this->resource->meta_description[$lang] ?? $this->resource->meta_description[app()->getLocale()] ?? null,
            'meta_keywords'    => $this->resource->meta_keywords[$lang] ?? $this->resource->meta_keywords[app()->getLocale()] ?? null,
            'slug'             => $this->resource->slug[$lang] ?? $this->resource->slug[app()->getLocale()] ?? null,
            'articles_per_page' => $this->resource->articles_per_page,
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
