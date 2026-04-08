<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Domains\Order\Models\OrderAddress;
use App\Domains\Order\Models\OrderItem;
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
}
