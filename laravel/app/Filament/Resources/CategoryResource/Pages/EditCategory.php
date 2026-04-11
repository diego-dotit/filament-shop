<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use MutatesTranslatableFields;

    protected static string $resource = CategoryResource::class;

    /**
     * Category uses Spatie dot-notation for translatable fields (name.en, etc.)
     * which Filament + Spatie handle natively, so no underscore-to-JSON conversion
     * is needed. The trait is used only for slug extraction and persistence.
     *
     * @var list<string>
     */
    protected array $translatableFields = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
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
}
