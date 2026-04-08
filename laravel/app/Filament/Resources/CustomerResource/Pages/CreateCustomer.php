<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Domains\Customer\Models\CustomerAddress;
use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /** @var array<int, array<string, mixed>> */
    private array $pendingAddresses = [];

    /**
     * Strip the addresses array from form data before Customer::create() is called,
     * storing it for later persistence in afterCreate().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingAddresses = $data['addresses'] ?? [];
        unset($data['addresses']);

        return $data;
    }

    /**
     * After the customer record has been persisted, create all pending address records
     * via the HasMany relationship so that customer_id is set automatically.
     */
    protected function afterCreate(): void
    {
        foreach ($this->pendingAddresses as $address) {
            $this->record->addresses()->create([...$address]);
        }
    }
}
