<?php

namespace App\Filament\Resources\BlogArticleResource\Pages;

use App\Filament\Resources\BlogArticleResource;
use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogArticle extends CreateRecord
{
    use MutatesTranslatableFields;

    protected static string $resource = BlogArticleResource::class;

    protected function getTranslatableFields(): array
    {
        return ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
