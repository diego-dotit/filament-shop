<?php

namespace App\Filament\Resources\ManufacturerResource\Pages;

use App\Filament\Resources\ManufacturerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturer extends CreateRecord
{
    protected static string $resource = ManufacturerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Persist the manually-entered slug value to the polymorphic slugs table.
     *
     * HasSlugs::booted() already fires on `saved` and may create a slug entry
     * derived from the model name. We use updateOrCreate so that a manual
     * override always wins regardless of whether HasSlugs already wrote a row.
     */
    protected function afterCreate(): void
    {
        $slug = $this->data['slug'] ?? null;

        if ($slug !== null && $slug !== '') {
            $locale = config('app.locale', 'en');

            $this->record->slugs()->updateOrCreate(
                ['locale' => $locale],
                ['slug'   => $slug],
            );
        }
    }
}
