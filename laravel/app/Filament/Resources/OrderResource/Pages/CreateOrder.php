<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /** @var array<int, array<string, mixed>> */
    private array $pendingItems = [];

    /** @var array<int, array<string, mixed>> */
    private array $pendingBillingAddresses = [];

    /** @var array<int, array<string, mixed>> */
    private array $pendingShippingAddresses = [];

    /**
     * Strip items and addresses arrays from form data before Order::create() is called,
     * storing them for later persistence in afterCreate().
     *
     * When the 'same_as_billing' switch is ON, billing address fields are copied to the
     * shipping address record (preserving shipping=1).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sameAsBilling = $data['same_as_billing'] ?? false;
        unset($data['same_as_billing']);

        if ($sameAsBilling && ! empty($data['billing_addresses'][0])) {
            $billingFields = array_intersect_key($data['billing_addresses'][0], array_flip([
                'firstname',
                'lastname',
                'business',
                'company',
                'company_id',
                'tax_id',
                'country_id',
                'zone_id',
                'city_id',
                'address_line_1',
                'address_line_2',
                'postcode',
            ]));

            $data['shipping_addresses'][0] = array_merge(
                $data['shipping_addresses'][0] ?? [],
                $billingFields,
                ['shipping' => 1],
            );
        }

        $this->pendingItems = $data['items'] ?? [];
        $this->pendingBillingAddresses = $data['billing_addresses'] ?? [];
        $this->pendingShippingAddresses = $data['shipping_addresses'] ?? [];

        unset($data['items'], $data['billing_addresses'], $data['shipping_addresses']);

        return $data;
    }

    /**
     * After the order record has been persisted, create all pending order items and
     * address records via Eloquent relationships so that order_id is set automatically.
     */
    protected function afterCreate(): void
    {
        foreach ($this->pendingItems as $item) {
            $this->record->items()->create([...$item]);
        }

        foreach ($this->pendingBillingAddresses as $address) {
            $this->record->addresses()->create([...$address]);
        }

        foreach ($this->pendingShippingAddresses as $address) {
            $this->record->addresses()->create([...$address]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
