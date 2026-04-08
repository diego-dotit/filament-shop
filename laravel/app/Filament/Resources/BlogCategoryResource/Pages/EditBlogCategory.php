<?php

namespace App\Filament\Resources\BlogCategoryResource\Pages;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\BlogCategoryResource;
use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlogCategory extends EditRecord
{
    use MutatesTranslatableFields;

    protected static string $resource = BlogCategoryResource::class;

    protected function getTranslatableFields(): array
    {
        return ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
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
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $languages = Language::all();

        foreach ($languages as $language) {
            $code = $language->code;

            $data["title_{$code}"] = $this->record->getTranslation('title', $code, false);
            $data["description_{$code}"] = $this->record->getTranslation('description', $code, false);
            $data["meta_title_{$code}"] = $this->record->getTranslation('meta_title', $code, false);
            $data["meta_description_{$code}"] = $this->record->getTranslation('meta_description', $code, false);
            $data["meta_keywords_{$code}"] = $this->record->getTranslation('meta_keywords', $code, false);
            $data["slug_{$code}"] = $this->record->getSlugForLocale($code)?->slug ?? '';
        }

        return $data;
    }
}
