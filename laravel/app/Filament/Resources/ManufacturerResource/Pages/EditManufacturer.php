<?php

namespace App\Filament\Resources\ManufacturerResource\Pages;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use App\Filament\Resources\ManufacturerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManufacturer extends EditRecord
{
    use MutatesTranslatableFields;

    protected static string $resource = ManufacturerResource::class;

    protected function getTranslatableFields(): array
    {
        return ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
    }

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
     * Expand stored JSON translations into flat per-locale fields so Filament
     * can populate each translation tab input individually.
     * Also pre-populates per-locale slug fields from the polymorphic slugs table.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $languages = Language::all();

        foreach ($languages as $language) {
            $code = $language->code;

            $data["name_{$code}"]             = $this->record->getTranslation('name', $code, false);
            $data["description_{$code}"]      = $this->record->getTranslation('description', $code, false);
            $data["meta_title_{$code}"]       = $this->record->getTranslation('meta_title', $code, false);
            $data["meta_description_{$code}"] = $this->record->getTranslation('meta_description', $code, false);
            $data["meta_keywords_{$code}"]    = $this->record->getTranslation('meta_keywords', $code, false);
            $data["slug_{$code}"]             = $this->record->getSlugForLocale($code)?->slug;
        }

        return $data;
    }
}
