<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    /** @var array<string, string> Keyed by locale code, value is the slug string. */
    protected array $pendingSlugs = [];

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Extract slug_{code} fields from form data so they are not passed to the
     * Eloquent create() call (they are not real database columns). The default
     * locale slug is promoted to data['slug'] which IS a column on categories.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $languages = Language::all();
        $defaultCode = Language::where('is_default', true)->first()?->code ?? 'en';

        foreach ($languages as $language) {
            $key = "slug_{$language->code}";
            if (array_key_exists($key, $data)) {
                $slugValue = $data[$key] ?? '';
                if ($slugValue !== '') {
                    $this->pendingSlugs[$language->code] = $slugValue;
                }
                unset($data[$key]);
            }
        }

        // Also handle the fallback case where no languages exist in DB yet
        // (the form would have used a static 'en' object)
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'slug_')) {
                $code = substr($key, 5);
                $slugValue = $data[$key] ?? '';
                if ($slugValue !== '') {
                    $this->pendingSlugs[$code] = $slugValue;
                }
                unset($data[$key]);
            }
        }

        // Set the categories.slug column from the default locale slug
        if (isset($this->pendingSlugs[$defaultCode])) {
            $data['slug'] = $this->pendingSlugs[$defaultCode];
        } elseif (! empty($this->pendingSlugs)) {
            $data['slug'] = reset($this->pendingSlugs);
        }

        return $data;
    }

    /**
     * After the category record is created, persist each locale's slug to the
     * polymorphic slugs table.  updateOrCreate ensures manual overrides win
     * over any slug HasSlugs may have auto-generated on the saved event.
     */
    protected function afterCreate(): void
    {
        foreach ($this->pendingSlugs as $locale => $slug) {
            $this->record->slugs()->updateOrCreate(
                ['locale' => $locale],
                ['slug'   => $slug],
            );
        }
    }
}
