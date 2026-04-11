<?php

namespace App\Filament\Resources\Product\Pages;

use App\Filament\Resources\Product\Pages\Concerns\MutatesProductTranslations;
use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use MutatesProductTranslations;

    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
