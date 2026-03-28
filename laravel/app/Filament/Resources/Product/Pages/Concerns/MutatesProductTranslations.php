<?php

namespace App\Filament\Resources\Product\Pages\Concerns;

use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use Illuminate\Support\Str;

trait MutatesProductTranslations
{
    /**
     * Pending slug data to be persisted after the model is saved.
     *
     * @var array<string, string>
     */
    private array $pendingSlugs = [];

    /**
     * Called by CreateRecord before persisting. Converts per-locale flat fields
     * (name_en, description_de, …) into JSON translation arrays.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->buildTranslationData($data);
    }

    /**
     * Called by EditRecord before persisting. Same conversion as create.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->buildTranslationData($data);
    }

    /**
     * After a new product is created, persist any pending slug overrides.
     */
    protected function afterCreate(): void
    {
        $this->persistPendingSlugs();
    }

    /**
     * After an existing product is saved, persist any pending slug overrides.
     */
    protected function afterSave(): void
    {
        $this->persistPendingSlugs();
    }

    /**
     * Collect per-locale flat fields (name_en, description_de, …) into the
     * JSON translation arrays that Spatie HasTranslations expects.
     * Also captures slug_{code} fields for later persistence.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildTranslationData(array $data): array
    {
        $languages       = Language::orderByDesc('is_default')->orderBy('name')->get();
        $defaultLanguage = $languages->firstWhere('is_default', true);

        $nameTranslations        = [];
        $descriptionTranslations = [];
        $slugData                = [];

        foreach ($languages as $language) {
            $code = $language->code;

            $nameValue = $data["name_{$code}"] ?? null;
            if ($nameValue !== null && $nameValue !== '') {
                $nameTranslations[$code] = $nameValue;
            }

            $descValue = $data["description_{$code}"] ?? null;
            if ($descValue !== null && $descValue !== '') {
                $descriptionTranslations[$code] = $descValue;
            }

            $slugValue = $data["slug_{$code}"] ?? null;
            if ($slugValue !== null && $slugValue !== '') {
                $slugData[$code] = $slugValue;
            }

            unset($data["name_{$code}"], $data["description_{$code}"], $data["slug_{$code}"]);
        }

        // Derive the canonical products.slug from the default locale slug,
        // falling back to a generated slug from the default locale name.
        if (! isset($data['slug']) || $data['slug'] === '' || $data['slug'] === null) {
            $defaultCode = $defaultLanguage?->code;
            if ($defaultCode && isset($slugData[$defaultCode])) {
                $data['slug'] = $slugData[$defaultCode];
            } elseif ($defaultCode && isset($nameTranslations[$defaultCode])) {
                $data['slug'] = Str::slug($nameTranslations[$defaultCode]);
            }
        }

        $this->pendingSlugs = $slugData;

        $data['name']        = $nameTranslations;
        $data['description'] = $descriptionTranslations ?: null;

        return $data;
    }

    /**
     * Upsert pending slug overrides into the slugs table.
     * Called after the model is created or updated.
     */
    private function persistPendingSlugs(): void
    {
        /** @var Product $product */
        $product = $this->record;

        foreach ($this->pendingSlugs as $locale => $slug) {
            $product->slugs()->updateOrCreate(
                ['locale' => $locale],
                ['slug'   => $slug],
            );
        }

        $this->pendingSlugs = [];
    }
}
