<?php

namespace App\Filament\Resources\ManufacturerResource\Pages;

use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use App\Filament\Resources\ManufacturerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturer extends CreateRecord
{
    use MutatesTranslatableFields;

    protected static string $resource = ManufacturerResource::class;

    protected function getTranslatableFields(): array
    {
        return ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
