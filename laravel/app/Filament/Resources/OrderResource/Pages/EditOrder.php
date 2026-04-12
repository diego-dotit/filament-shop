<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Domains\Order\Models\OrderAddress;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Order\Models\OrderTotal;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /** @var array<int, array<string, mixed>> */
    private array $pendingItems = [];

    /** @var array<int, array<string, mixed>> */
    private array $pendingBillingAddresses = [];

    /** @var array<int, array<string, mixed>> */
    private array $pendingShippingAddresses = [];

    /** @var array<int, array<string, mixed>> */
    private array $pendingTotals = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load('items', 'addresses', 'totals');

        $data['items'] = $this->record->items
            ->map(fn (OrderItem $item) => $item->only([
                'product_id',
                'product_variant_id',
                'quantity',
                'unit_price_snapshot',
            ]))
            ->toArray();

        $addressFields = [
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
            'shipping',
        ];

        $data['billing_addresses'] = $this->record->addresses
            ->where('shipping', 0)
            ->map(fn (OrderAddress $address) => $address->only($addressFields))
            ->values()
            ->toArray();

        $data['shipping_addresses'] = $this->record->addresses
            ->where('shipping', 1)
            ->map(fn (OrderAddress $address) => $address->only($addressFields))
            ->values()
            ->toArray();

        $data['same_as_billing'] = $this->detectSameAsBilling(
            $data['billing_addresses'][0] ?? [],
            $data['shipping_addresses'][0] ?? []
        );

        $data['order_totals'] = $this->record->totals
            ->where('code', '!=', 'total')
            ->sortBy('sort_order')
            ->values()
            ->map(fn (OrderTotal $total) => $total->only(['name', 'code', 'value', 'sort_order']))
            ->toArray();

        return $data;
    }

    private function detectSameAsBilling(array $billing, array $shipping): bool
    {
        if (empty($billing) || empty($shipping)) {
            return false;
        }

        $compareFields = [
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
        ];

        foreach ($compareFields as $field) {
            if (($billing[$field] ?? null) != ($shipping[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $sameAsBilling = (bool) ($data['same_as_billing'] ?? false);

        $this->pendingItems = $data['items'] ?? [];
        $this->pendingBillingAddresses = $data['billing_addresses'] ?? [];
        $this->pendingShippingAddresses = $data['shipping_addresses'] ?? [];
        $this->pendingTotals = $data['order_totals'] ?? [];

        if ($sameAsBilling && ! empty($this->pendingBillingAddresses[0])) {
            $copyFields = [
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
            ];

            $shippingBase = $this->pendingShippingAddresses[0] ?? [];

            foreach ($copyFields as $field) {
                $shippingBase[$field] = $this->pendingBillingAddresses[0][$field] ?? null;
            }

            $shippingBase['shipping'] = 1;

            $this->pendingShippingAddresses[0] = $shippingBase;
        }

        unset($data['items'], $data['billing_addresses'], $data['shipping_addresses'], $data['same_as_billing'], $data['order_totals']);

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

        $this->record->totals()->where('code', '!=', 'total')->delete();
        foreach ($this->pendingTotals as $index => $item) {
            $item['sort_order'] = $index + 1;
            $this->record->totals()->create($item);
        }

        $maxSortOrder = $this->record->totals()->where('code', '!=', 'total')->max('sort_order') ?? 0;

        $systemTotal = $this->record->totals()->where('code', 'total')->first();

        if ($systemTotal) {
            $systemTotal->update(['sort_order' => $maxSortOrder + 1]);
        } else {
            $this->record->totals()->create([
                'name' => 'Total',
                'code' => 'total',
                'value' => 0,
                'sort_order' => $maxSortOrder > 0 ? $maxSortOrder + 1 : 999,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
