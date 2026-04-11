<?php

namespace App\Filament\Resources\BlogCategoryResource\Pages;

use App\Filament\Resources\BlogCategoryResource;
use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogCategory extends CreateRecord
{
    use MutatesTranslatableFields;

    protected static string $resource = BlogCategoryResource::class;

    protected function getTranslatableFields(): array
    {
        return ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
