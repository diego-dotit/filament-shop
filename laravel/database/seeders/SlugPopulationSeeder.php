<?php

namespace Database\Seeders;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Product\Models\Product;
use App\Domains\Slug\Models\Slug;
use Illuminate\Database\Seeder;

class SlugPopulationSeeder extends Seeder
{
    /**
     * Migrate existing slug values from entity tables into the global slugs table.
     *
     * Idempotent: uses firstOrCreate keyed on (sluggable_type, sluggable_id, locale)
     * to avoid duplicates when run multiple times.
     */
    public function run(): void
    {
        $defaultLanguage = Language::where('is_default', true)->first();

        if ($defaultLanguage === null) {
            $this->command?->warn('No default language found. Skipping SlugPopulationSeeder.');
            return;
        }

        $locale = $defaultLanguage->code;

        $this->migrateEntitySlugs(Product::class, $locale);
        $this->migrateEntitySlugs(Category::class, $locale);
        $this->migrateEntitySlugs(Manufacturer::class, $locale);
    }

    /**
     * Iterate all records of a given model class and create slug entries
     * for those that have a non-empty slug value.
     *
     * @param  class-string  $modelClass
     */
    private function migrateEntitySlugs(string $modelClass, string $locale): void
    {
        $modelClass::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->each(function ($entity) use ($modelClass, $locale): void {
                Slug::firstOrCreate(
                    [
                        'sluggable_type' => $modelClass,
                        'sluggable_id'   => $entity->id,
                        'locale'         => $locale,
                    ],
                    [
                        'slug' => $entity->slug,
                    ]
                );
            });
    }
}
