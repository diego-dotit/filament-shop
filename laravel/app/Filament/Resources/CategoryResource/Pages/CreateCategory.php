<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
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

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
