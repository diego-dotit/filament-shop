<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Domains\Order\Models\OrderAddress;
use App\Domains\Order\Models\OrderItem;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    private array $pendingItems = [];
    private array $pendingBillingAddresses = [];
    private array $pendingShippingAddresses = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load('items', 'addresses');

        $data['items'] = $this->record->items
            ->map(fn (OrderItem $item) => $item->only([
                'product_id',
                'product_variant_id',
                'quantity',
                'unit_price_snapshot',
            ]))
            ->toArray();

        $data['billing_addresses'] = $this->record->addresses
            ->where('type', 'billing')
            ->map(fn (OrderAddress $address) => $address->only([
                'country',
                'city',
                'address_line_1',
                'address_line_2',
                'postcode',
                'type',
            ]))
            ->values()
            ->toArray();

        $data['shipping_addresses'] = $this->record->addresses
            ->where('type', 'shipping')
            ->map(fn (OrderAddress $address) => $address->only([
                'country',
                'city',
                'address_line_1',
                'address_line_2',
                'postcode',
                'type',
            ]))
            ->values()
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingItems = $data['items'] ?? [];
        $this->pendingBillingAddresses = $data['billing_addresses'] ?? [];
        $this->pendingShippingAddresses = $data['shipping_addresses'] ?? [];

        unset($data['items'], $data['billing_addresses'], $data['shipping_addresses']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->items()->delete();
        foreach ($this->pendingItems as $item) {
            $this->record->items()->create($item);
        }

        $this->record->addresses()->delete();
        foreach ($this->pendingBillingAddresses as $address) {
            $this->record->addresses()->create($address);
        }
        foreach ($this->pendingShippingAddresses as $address) {
            $this->record->addresses()->create($address);
        }
    }
}
