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
     * Translatable fields that use per-locale flat keys (field_en, field_de, …)
     * and are collapsed into JSON translation arrays before persistence.
     *
     * @var list<string>
     */
    private const TRANSLATABLE_FIELDS = [
        'name',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * Collect per-locale flat fields (name_en, description_de, meta_title_fr, …)
     * into the JSON translation arrays that Spatie HasTranslations expects.
     * Also captures slug_{code} fields for later persistence.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildTranslationData(array $data): array
    {
        $languages       = Language::orderByDesc('is_default')->orderBy('name')->get();
        $defaultLanguage = $languages->firstWhere('is_default', true);

        /** @var array<string, array<string, string>> $translations */
        $translations = array_fill_keys(self::TRANSLATABLE_FIELDS, []);
        $slugData     = [];

        foreach ($languages as $language) {
            $code = $language->code;

            foreach (self::TRANSLATABLE_FIELDS as $field) {
                $value = $data["{$field}_{$code}"] ?? null;
                if ($value !== null && $value !== '') {
                    $translations[$field][$code] = $value;
                }
                unset($data["{$field}_{$code}"]);
            }

            $slugValue = $data["slug_{$code}"] ?? null;
            if ($slugValue !== null && $slugValue !== '') {
                $slugData[$code] = $slugValue;
            }
            unset($data["slug_{$code}"]);
        }

        // Derive the canonical products.slug from the default locale slug,
        // falling back to a generated slug from the default locale name.
        if (! isset($data['slug']) || $data['slug'] === '' || $data['slug'] === null) {
            $defaultCode = $defaultLanguage?->code;
            if ($defaultCode && isset($slugData[$defaultCode])) {
                $data['slug'] = $slugData[$defaultCode];
            } elseif ($defaultCode && isset($translations['name'][$defaultCode])) {
                $data['slug'] = Str::slug($translations['name'][$defaultCode]);
            }
        }

        $this->pendingSlugs = $slugData;

        $data['name']             = $translations['name'];
        $data['description']      = $translations['description'] ?: null;
        $data['meta_title']       = $translations['meta_title'] ?: null;
        $data['meta_description'] = $translations['meta_description'] ?: null;
        $data['meta_keywords']    = $translations['meta_keywords'] ?: null;

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
