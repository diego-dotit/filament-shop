<?php

namespace App\Filament\Resources\ManufacturerResource\Pages;

use App\Filament\Resources\ManufacturerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManufacturer extends EditRecord
{
    protected static string $resource = ManufacturerResource::class;

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
     * Pre-populate the slug field from the polymorphic slugs table.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $locale = config('app.locale', 'en');

        $slug = $this->record->getSlugForLocale($locale)
            ?? $this->record->slugs()->first();

        $data['slug'] = $slug?->slug ?? '';

        return $data;
    }

    /**
     * Persist the manually-entered slug value to the polymorphic slugs table.
     */
    protected function afterSave(): void
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
