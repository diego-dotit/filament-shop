<?php

namespace Tests\Unit\Resources;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Order\Models\OrderAddress;
use App\Http\Resources\Api\Order\OrderResource;
use App\Http\Resources\Api\Order\OrderItemResource;
use App\Http\Resources\Api\Order\OrderAddressResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    public function test_order_resource_has_expected_keys(): void
    {
        $order = $this->makeOrder();

        $resource = new OrderResource($order);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('total_amount', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('billing_address', $data);
        $this->assertArrayHasKey('shipping_address', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function test_order_resource_total_amount_is_converted_price(): void
    {
        $order = $this->makeOrder();

        $resource = new OrderResource($order);
        $data = $resource->toArray(Request::create('/'));

        // total_amount should be a converted decimal number (float), not a structured array
        $this->assertIsFloat($data['total_amount']);
        $this->assertEqualsWithDelta(250.0, $data['total_amount'], 0.001);
    }

    public function test_order_resource_total_amount_converted_with_request_currency(): void
    {
        $order = $this->makeOrder();

        $currency = new \App\Domains\Currency\Models\Currency();
        $currency->setRawAttributes(['exchange_rate' => '2.00', 'is_base' => false]);

        $request = Request::create('/');
        $request->attributes->set('currency', $currency);

        $resource = new OrderResource($order);
        $data = $resource->toArray($request);

        // 250.00 * 2.00 = 500.00
        $this->assertIsFloat($data['total_amount']);
        $this->assertEqualsWithDelta(500.0, $data['total_amount'], 0.001);
    }

    public function test_order_item_resource_has_expected_keys(): void
    {
        $item = new OrderItem();
        $item->setRawAttributes([
            'id'                    => 1,
            'product_name_snapshot' => 'Blue T-Shirt',
            'variant_sku_snapshot'  => 'TS-BL-L',
            'unit_price_snapshot'   => '19.99',
            'quantity'              => 3,
            'line_total_snapshot'   => '59.97',
        ]);

        $resource = new OrderItemResource($item);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('product_name_snapshot', $data);
        $this->assertArrayHasKey('variant_sku_snapshot', $data);
        $this->assertArrayHasKey('unit_price_snapshot', $data);
        $this->assertArrayHasKey('quantity', $data);
        $this->assertArrayHasKey('line_total_snapshot', $data);
    }

    public function test_order_item_resource_maps_values(): void
    {
        $item = new OrderItem();
        $item->setRawAttributes([
            'product_name_snapshot' => 'Red Hoodie',
            'variant_sku_snapshot'  => 'HO-RD-M',
            'unit_price_snapshot'   => '49.99',
            'quantity'              => 2,
            'line_total_snapshot'   => '99.98',
        ]);

        $resource = new OrderItemResource($item);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('Red Hoodie', $data['product_name_snapshot']);
        $this->assertSame('HO-RD-M', $data['variant_sku_snapshot']);
        $this->assertSame(2, $data['quantity']);
    }

    public function test_order_item_resource_formats_snapshot_prices_through_service(): void
    {
        $item = new OrderItem();
        $item->setRawAttributes([
            'product_name_snapshot' => 'Blue Shirt',
            'variant_sku_snapshot'  => 'SH-BL-M',
            'unit_price_snapshot'   => '15.00',
            'quantity'              => 3,
            'line_total_snapshot'   => '45.00',
        ]);

        $resource = new OrderItemResource($item);
        $data = $resource->toArray(Request::create('/'));

        // Snapshot prices are formatted with two-decimal precision (float)
        $this->assertEqualsWithDelta(15.0, $data['unit_price_snapshot'], 0.001);
        $this->assertEqualsWithDelta(45.0, $data['line_total_snapshot'], 0.001);
    }

    public function test_order_item_resource_snapshot_prices_formatted_to_two_decimals(): void
    {
        $item = new OrderItem();
        $item->setRawAttributes([
            'product_name_snapshot' => 'Widget',
            'variant_sku_snapshot'  => 'WG-001',
            'unit_price_snapshot'   => '7.5',
            'quantity'              => 1,
            'line_total_snapshot'   => '7.5',
        ]);

        $resource = new OrderItemResource($item);
        $data = $resource->toArray(Request::create('/'));

        // Prices run through CurrencyService.convertPrice → always two-decimal float
        $this->assertEqualsWithDelta(7.5, $data['unit_price_snapshot'], 0.001);
        $this->assertEqualsWithDelta(7.5, $data['line_total_snapshot'], 0.001);
    }

    public function test_order_address_resource_has_expected_keys(): void
    {
        $address = new OrderAddress();
        $address->setRawAttributes([
            'type'           => 'billing',
            'country'        => 'US',
            'city'           => 'New York',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'postcode'       => '10001',
        ]);

        $resource = new OrderAddressResource($address);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('country', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('address_line_1', $data);
        $this->assertArrayHasKey('address_line_2', $data);
        $this->assertArrayHasKey('postcode', $data);
    }

    public function test_order_address_resource_maps_values(): void
    {
        $address = new OrderAddress();
        $address->setRawAttributes([
            'type'           => 'shipping',
            'country'        => 'GB',
            'city'           => 'London',
            'address_line_1' => '10 Downing St',
            'address_line_2' => null,
            'postcode'       => 'SW1A 2AA',
        ]);

        $resource = new OrderAddressResource($address);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('shipping', $data['type']);
        $this->assertSame('GB', $data['country']);
        $this->assertSame('London', $data['city']);
        $this->assertSame('SW1A 2AA', $data['postcode']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeOrder(): Order
    {
        $order = new Order();
        $order->setRawAttributes([
            'id'            => 1,
            'status'        => 'pending',
            'total_amount'  => '250.00',
            'currency_code' => 'USD',
            'exchange_rate' => '1.000000',
            'created_at'    => '2024-03-15 08:00:00',
        ]);
        $order->setRelation('items', new Collection([]));
        $order->setRelation('addresses', new Collection([]));

        return $order;
    }
}
