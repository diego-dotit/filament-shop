<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    /** @var array<string, string> Keyed by locale code, value is the slug string. */
    protected array $pendingSlugs = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Pre-populate per-locale slug_{code} fields from the polymorphic slugs table
     * so that each language tab's slug input is filled when editing an existing category.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $languages = Language::all();

        foreach ($languages as $language) {
            $code = $language->code;
            $data["slug_{$code}"] = $this->record->getSlugForLocale($code)?->slug;
        }

        return $data;
    }

    /**
     * Extract slug_{code} fields from form data so they are not passed to the
     * Eloquent save() call (they are not real database columns). The default
     * locale slug is promoted to data['slug'] which IS a column on categories.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
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

        // Handle any remaining slug_{code} keys not covered by DB languages
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
     * After the category record is saved, persist each locale's slug to the
     * polymorphic slugs table.  updateOrCreate ensures manual overrides win
     * over any slug HasSlugs may have auto-generated on the saved event.
     */
    protected function afterSave(): void
    {
        foreach ($this->pendingSlugs as $locale => $slug) {
            $this->record->slugs()->updateOrCreate(
                ['locale' => $locale],
                ['slug'   => $slug],
            );
        }
    }
}
