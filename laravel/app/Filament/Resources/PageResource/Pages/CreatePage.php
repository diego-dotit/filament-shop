<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use App\Filament\Resources\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use MutatesTranslatableFields;

    protected static string $resource = PageResource::class;

    protected function getTranslatableFields(): array
    {
        return ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
