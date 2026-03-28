<?php

namespace App\Domains\Shared\Traits;

use App\Domains\Language\Models\Language;
use App\Domains\Slug\Models\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

trait HasSlugs
{
    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    public function getSlugForLocale(string $locale): ?Slug
    {
        return $this->slugs()->where('locale', $locale)->first();
    }

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            $locales = Language::all()->pluck('code');

            foreach ($locales as $locale) {
                // Determine the name for this locale
                if (isset($model->translatable) && in_array('name', $model->translatable, true)) {
                    $name = $model->getTranslation('name', $locale, false);
                } else {
                    $name = $model->name ?? null;
                }

                if (empty($name)) {
                    continue;
                }

                $generatedSlug = Str::slug($name);

                $existing = $model->slugs()->where('locale', $locale)->first();

                if ($existing !== null) {
                    // Only update the stored slug when the name field was changed in
                    // this save operation; leave manually-customised slugs intact.
                    if ($model->wasChanged('name')) {
                        try {
                            $existing->update(['slug' => $generatedSlug]);
                        } catch (UniqueConstraintViolationException) {
                            // New slug conflicts globally; preserve the existing value.
                        }
                    }
                    continue;
                }

                try {
                    $model->slugs()->create([
                        'sluggable_type' => get_class($model),
                        'sluggable_id'   => $model->id,
                        'locale'         => $locale,
                        'slug'           => $generatedSlug,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Slug already exists globally (e.g. same value for a different locale
                    // of a non-translatable model); skip silently.
                }
            }
        });
    }
}
