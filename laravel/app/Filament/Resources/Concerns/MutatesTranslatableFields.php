<?php

namespace App\Filament\Resources\Concerns;

use App\Domains\Language\Models\Language;
use Illuminate\Support\Str;

/**
 * Shared Filament page concern for handling translatable field mutation.
 *
 * Mix this trait into any Filament Create or Edit page class that needs to:
 *  1. Convert per-locale flat form fields (e.g. name_en, meta_title_de) into
 *     the JSON translation arrays that Spatie HasTranslations expects.
 *  2. Extract slug_{code} fields and persist them to the polymorphic slugs
 *     table after the record is saved.
 *
 * Usage
 * -----
 * In your Create/Edit page class, declare the trait and configure fields:
 *
 *   use MutatesTranslatableFields;
 *
 *   protected array $translatableFields = ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
 *
 * If your form already uses Spatie's dot-notation (e.g. `name.en`) and Filament
 * handles the JSON mapping natively, set $translatableFields = [] and the trait
 * will only take care of slug extraction/persistence.
 */
trait MutatesTranslatableFields
{
    /**
     * Names of the translatable model fields that use the underscore-locale
     * convention in form inputs (e.g. `name_en`, `description_fr`).
     *
     * Override this in the host class for resource-specific field lists.
     * Alternatively, override getTranslatableFields() to return the list
     * when setting a different default value would conflict with this
     * trait's property declaration (PHP does not allow re-declaration with
     * a different initial value).
     *
     * @var list<string>
     */
    protected array $translatableFields = [];

    /**
     * Returns the list of translatable fields.
     * Override this method in the host class to customise the field list
     * without touching the property (useful when the class cannot re-declare
     * the same-named trait property with a different default value).
     *
     * @return list<string>
     */
    protected function getTranslatableFields(): array
    {
        return $this->translatableFields;
    }

    /**
     * Pending slug data to be persisted after the model is saved.
     * Keyed by locale code, value is the slug string.
     *
     * @var array<string, string>
     */
    protected array $pendingSlugs = [];

    /**
     * Called by Filament's CreateRecord before persisting.
     * Converts per-locale flat fields into JSON translation arrays.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->buildTranslationData($data);
    }

    /**
     * Called by Filament's EditRecord before persisting.
     * Same conversion as create.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->buildTranslationData($data);
    }

    /**
     * After a new record is created, persist any pending slug overrides.
     */
    protected function afterCreate(): void
    {
        $this->persistPendingSlugs();
    }

    /**
     * After an existing record is saved, persist any pending slug overrides.
     */
    protected function afterSave(): void
    {
        $this->persistPendingSlugs();
    }

    /**
     * Convert per-locale flat form fields into JSON translation arrays and
     * collect slug_{code} fields for later persistence.
     *
     * For each field in $translatableFields, the method expects form inputs
     * named `{field}_{code}` (e.g. `name_en`, `meta_title_de`). These are
     * collected into `$data[$field][$code]` and removed from $data so they
     * do not reach the Eloquent create/save call as raw columns.
     *
     * Slug fields (`slug_{code}`) are always extracted and stored in
     * $this->pendingSlugs regardless of $translatableFields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildTranslationData(array $data): array
    {
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        // Initialise empty arrays for every translatable field so that the
        // key always exists in the returned $data (even if all values are empty).
        $translations = [];
        foreach ($this->getTranslatableFields() as $field) {
            $translations[$field] = [];
        }

        $slugData = [];

        foreach ($languages as $language) {
            $code = $language->code;

            // Collect translatable field values for this locale
            foreach ($this->getTranslatableFields() as $field) {
                $value = $data["{$field}_{$code}"] ?? null;
                if ($value !== null && $value !== '') {
                    $translations[$field][$code] = $value;
                }
                unset($data["{$field}_{$code}"]);
            }

            // Always collect and strip slug fields
            $slugValue = $data["slug_{$code}"] ?? null;
            if ($slugValue !== null && $slugValue !== '') {
                $slugData[$code] = $slugValue;
            }
            unset($data["slug_{$code}"]);
        }

        // Fallback: collect any remaining slug_{code} keys that were not covered
        // by languages in the database (e.g. when the languages table is empty or
        // the form uses a static fallback locale such as 'en'). This mirrors the
        // defensive pattern from the original per-resource page implementations.
        foreach (array_keys($data) as $key) {
            if (! str_starts_with($key, 'slug_')) {
                continue;
            }

            $code      = substr($key, 5);
            $slugValue = $data[$key] ?? '';
            if ($slugValue !== '') {
                $slugData[$code] = $slugValue;
            }
            unset($data[$key]);
        }

        // Merge collected translation arrays back into $data
        foreach ($this->getTranslatableFields() as $field) {
            $data[$field] = $translations[$field];
        }

        $this->pendingSlugs = $slugData;

        // Promote the default-locale slug to $data['slug'] when the model has
        // a dedicated slug column (e.g. categories.slug, products.slug).
        // Falls back to a Str::slug() of the default-locale name when the slug
        // field was not explicitly filled by the user, or to any first available
        // slug when no default language is configured in the database yet.
        if (! isset($data['slug']) || $data['slug'] === '' || $data['slug'] === null) {
            $defaultCode = $languages->firstWhere('is_default', true)?->code;

            if ($defaultCode !== null && isset($slugData[$defaultCode])) {
                $data['slug'] = $slugData[$defaultCode];
            } elseif (
                $defaultCode !== null
                && in_array('name', $this->getTranslatableFields(), true)
                && ! empty($translations['name'][$defaultCode])
            ) {
                $data['slug'] = Str::slug($translations['name'][$defaultCode]);
            } elseif (! empty($slugData)) {
                // No default language in DB; use the first available slug as canonical value.
                $data['slug'] = reset($slugData);
            }
        }

        return $data;
    }

    /**
     * Upsert pending slug overrides into the slugs table.
     * Called automatically after the record is created or updated.
     */
    protected function persistPendingSlugs(): void
    {
        $record = $this->record;

        foreach ($this->pendingSlugs as $locale => $slug) {
            $record->slugs()->updateOrCreate(
                ['locale' => $locale],
                ['slug'   => $slug],
            );
        }

        $this->pendingSlugs = [];
    }
}
