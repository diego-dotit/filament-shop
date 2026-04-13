<?php

namespace App\Filament\Resources\OrderResource\Pages;

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

    protected ?string $originalStatus = null;

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
        ];

        $billing = $this->record->addresses->firstWhere('shipping', 0);
        $shipping = $this->record->addresses->firstWhere('shipping', 1);

        foreach ($addressFields as $field) {
            $data['billing_'.$field] = $billing ? $billing->$field : null;
            $data['shipping_'.$field] = $shipping ? $shipping->$field : null;
        }

        $data['same_as_billing'] = $this->detectSameAsBilling($data);

        $data['order_totals'] = $this->record->totals
            ->where('code', '!=', 'total')
            ->sortBy('sort_order')
            ->values()
            ->map(fn (OrderTotal $total) => $total->only(['name', 'code', 'value', 'sort_order']))
            ->toArray();

        return $data;
    }

    private function detectSameAsBilling(array $data): bool
    {
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
            if (($data['billing_'.$field] ?? null) != ($data['shipping_'.$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalStatus = $this->record->status;

        $sameAsBilling = (bool) ($data['same_as_billing'] ?? false);

        $this->pendingItems = $data['items'] ?? [];
        $this->pendingTotals = $data['order_totals'] ?? [];

        $this->pendingBillingAddresses = [[
            'firstname' => $data['billing_firstname'] ?? null,
            'lastname' => $data['billing_lastname'] ?? null,
            'business' => $data['billing_business'] ?? false,
            'company' => $data['billing_company'] ?? null,
            'company_id' => $data['billing_company_id'] ?? null,
            'tax_id' => $data['billing_tax_id'] ?? null,
            'country_id' => $data['billing_country_id'] ?? null,
            'zone_id' => $data['billing_zone_id'] ?? null,
            'city_id' => $data['billing_city_id'] ?? null,
            'address_line_1' => $data['billing_address_line_1'] ?? null,
            'address_line_2' => $data['billing_address_line_2'] ?? null,
            'postcode' => $data['billing_postcode'] ?? null,
            'shipping' => 0,
        ]];

        if ($sameAsBilling) {
            $shippingEntry = $this->pendingBillingAddresses[0];
            $shippingEntry['shipping'] = 1;
            $this->pendingShippingAddresses = [$shippingEntry];
        } else {
            $this->pendingShippingAddresses = [[
                'firstname' => $data['shipping_firstname'] ?? null,
                'lastname' => $data['shipping_lastname'] ?? null,
                'business' => $data['shipping_business'] ?? false,
                'company' => $data['shipping_company'] ?? null,
                'company_id' => $data['shipping_company_id'] ?? null,
                'tax_id' => $data['shipping_tax_id'] ?? null,
                'country_id' => $data['shipping_country_id'] ?? null,
                'zone_id' => $data['shipping_zone_id'] ?? null,
                'city_id' => $data['shipping_city_id'] ?? null,
                'address_line_1' => $data['shipping_address_line_1'] ?? null,
                'address_line_2' => $data['shipping_address_line_2'] ?? null,
                'postcode' => $data['shipping_postcode'] ?? null,
                'shipping' => 1,
            ]];
        }

        // Remove address keys and other non-model keys from $data before save
        $prefixes = ['billing_', 'shipping_'];
        foreach (array_keys($data) as $key) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    unset($data[$key]);
                    break;
                }
            }
        }

        unset($data['same_as_billing'], $data['items'], $data['order_totals']);

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

        if ($this->originalStatus !== $this->record->status) {
            $this->record->createHistoryEntry($this->record->status, null);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
