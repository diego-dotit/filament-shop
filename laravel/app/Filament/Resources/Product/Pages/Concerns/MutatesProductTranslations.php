<?php

namespace App\Filament\Resources\Product\Pages\Concerns;

use App\Domains\Language\Models\Language;

trait MutatesProductTranslations
{
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
     * Collect per-locale flat fields (name_en, description_de, …) into the
     * JSON translation arrays that Spatie HasTranslations expects.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildTranslationData(array $data): array
    {
        $languages = Language::all();

        $nameTranslations        = [];
        $descriptionTranslations = [];

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

            unset($data["name_{$code}"], $data["description_{$code}"]);
        }

        $data['name']        = $nameTranslations;
        $data['description'] = $descriptionTranslations ?: null;

        return $data;
    }
}
