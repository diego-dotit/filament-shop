<?php

namespace App\Filament\Resources\Product\Pages;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\Product\Pages\Concerns\MutatesProductTranslations;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use MutatesProductTranslations;

    protected static string $resource = ProductResource::class;

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
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $languages = Language::all();

        foreach ($languages as $language) {
            $code = $language->code;
            $data["name_{$code}"]        = $this->record->getTranslation('name', $code, false);
            $data["description_{$code}"] = $this->record->getTranslation('description', $code, false);
            $data["slug_{$code}"]        = $this->record->getSlugForLocale($code)?->slug;
        }

        return $data;
    }
}
