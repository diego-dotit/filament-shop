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

    /** @var array<int, array<string, mixed>> */
    private array $pendingTotals = [];

    /**
     * Strip items and addresses arrays from form data before Order::create() is called,
     * storing them for later persistence in afterCreate().
     *
     * Reads flat billing_* and shipping_* keys (no nested repeater arrays).
     * When the 'same_as_billing' switch is ON, billing address fields are copied to the
     * shipping address record (preserving shipping=1).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sameAsBilling = $data['same_as_billing'] ?? false;

        // Build billing address from flat billing_* keys
        $this->pendingBillingAddresses = [[
            'firstname'      => $data['billing_firstname'] ?? null,
            'lastname'       => $data['billing_lastname'] ?? null,
            'business'       => $data['billing_business'] ?? false,
            'company'        => $data['billing_company'] ?? null,
            'company_id'     => $data['billing_company_id'] ?? null,
            'tax_id'         => $data['billing_tax_id'] ?? null,
            'country_id'     => $data['billing_country_id'] ?? null,
            'zone_id'        => $data['billing_zone_id'] ?? null,
            'city_id'        => $data['billing_city_id'] ?? null,
            'address_line_1' => $data['billing_address_line_1'] ?? null,
            'address_line_2' => $data['billing_address_line_2'] ?? null,
            'postcode'       => $data['billing_postcode'] ?? null,
            'shipping'       => 0,
        ]];

        // Build shipping address — copy from billing when same_as_billing is set,
        // otherwise read flat shipping_* keys
        if ($sameAsBilling) {
            $shippingAddress            = $this->pendingBillingAddresses[0];
            $shippingAddress['shipping'] = 1;
            $this->pendingShippingAddresses = [$shippingAddress];
        } else {
            $this->pendingShippingAddresses = [[
                'firstname'      => $data['shipping_firstname'] ?? null,
                'lastname'       => $data['shipping_lastname'] ?? null,
                'business'       => $data['shipping_business'] ?? false,
                'company'        => $data['shipping_company'] ?? null,
                'company_id'     => $data['shipping_company_id'] ?? null,
                'tax_id'         => $data['shipping_tax_id'] ?? null,
                'country_id'     => $data['shipping_country_id'] ?? null,
                'zone_id'        => $data['shipping_zone_id'] ?? null,
                'city_id'        => $data['shipping_city_id'] ?? null,
                'address_line_1' => $data['shipping_address_line_1'] ?? null,
                'address_line_2' => $data['shipping_address_line_2'] ?? null,
                'postcode'       => $data['shipping_postcode'] ?? null,
                'shipping'       => 1,
            ]];
        }

        $this->pendingItems  = $data['items'] ?? [];
        $this->pendingTotals = $data['order_totals'] ?? [];

        // Remove all billing_* and shipping_* flat keys, plus other non-Order-column keys
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'billing_') || str_starts_with($key, 'shipping_')) {
                unset($data[$key]);
            }
        }

        unset($data['same_as_billing'], $data['items'], $data['order_totals']);

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

        $this->record->createHistoryEntry($this->record->status, null, $this->record->created_at);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
