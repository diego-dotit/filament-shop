<?php

namespace App\Http\Resources\Api\Blog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogArticleResource extends JsonResource
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
            'title'            => $this->getTranslation('title', $lang),
            'description'      => $this->getTranslation('description', $lang),
            'meta_title'       => $this->getTranslation('meta_title', $lang),
            'meta_description' => $this->getTranslation('meta_description', $lang),
            'meta_keywords'    => $this->getTranslation('meta_keywords', $lang),
            'slug'             => ($this->exists ? $this->getSlugForLocale($lang)?->slug : null) ?? $this->slug,
            'author'           => $this->author,
            'status'           => $this->status,
            'post_date'        => $this->post_date?->toDateString(),
            'thumbnail_url'    => $this->resolveThumbnail(),
            'categories'       => BlogCategoryResource::collection(
                $this->resource->relationLoaded('blogCategories')
                    ? $this->blogCategories
                    : collect()
            ),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
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

    private function resolveThumbnail(): ?string
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
