<?php

namespace Tests\Unit\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;
use App\Domains\Order\Models\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    // ──────────────────────────────── Order ───────────────────────────────

    public function test_order_fillable_contains_expected_fields(): void
    {
        $order = new Order();

        $this->assertSame(
            ['customer_id', 'status', 'total_amount', 'currency_code', 'exchange_rate'],
            $order->getFillable()
        );
    }

    public function test_order_casts_total_amount_as_decimal(): void
    {
        $order = new Order();

        $this->assertArrayHasKey('total_amount', $order->getCasts());
        $this->assertStringStartsWith('decimal', $order->getCasts()['total_amount']);
    }

    public function test_order_casts_exchange_rate_as_decimal(): void
    {
        $order = new Order();

        $this->assertArrayHasKey('exchange_rate', $order->getCasts());
        $this->assertStringStartsWith('decimal', $order->getCasts()['exchange_rate']);
    }

    public function test_order_has_customer_relationship_method(): void
    {
        $this->assertTrue(method_exists(Order::class, 'customer'));
    }

    public function test_order_has_items_relationship_method(): void
    {
        $this->assertTrue(method_exists(Order::class, 'items'));
    }

    public function test_order_has_addresses_relationship_method(): void
    {
        $this->assertTrue(method_exists(Order::class, 'addresses'));
    }

    public function test_order_casts_returns_array(): void
    {
        $this->assertIsArray((new Order())->getCasts());
    }

    // ──────────────────────────────── OrderItem ───────────────────────────

    public function test_order_item_fillable_contains_expected_fields(): void
    {
        $item = new OrderItem();

        $this->assertSame(
            ['order_id', 'product_id', 'product_variant_id', 'product_name_snapshot', 'variant_sku_snapshot', 'unit_price_snapshot', 'quantity', 'line_total_snapshot'],
            $item->getFillable()
        );
    }

    public function test_order_item_casts_unit_price_snapshot_as_decimal(): void
    {
        $item = new OrderItem();

        $this->assertArrayHasKey('unit_price_snapshot', $item->getCasts());
        $this->assertStringStartsWith('decimal', $item->getCasts()['unit_price_snapshot']);
    }

    public function test_order_item_casts_line_total_snapshot_as_decimal(): void
    {
        $item = new OrderItem();

        $this->assertArrayHasKey('line_total_snapshot', $item->getCasts());
        $this->assertStringStartsWith('decimal', $item->getCasts()['line_total_snapshot']);
    }

    public function test_order_item_casts_quantity_as_integer(): void
    {
        $item = new OrderItem();

        $this->assertArrayHasKey('quantity', $item->getCasts());
        $this->assertSame('integer', $item->getCasts()['quantity']);
    }

    public function test_order_item_has_order_relationship_method(): void
    {
        $this->assertTrue(method_exists(OrderItem::class, 'order'));
    }

    public function test_order_item_has_product_relationship_method(): void
    {
        $this->assertTrue(method_exists(OrderItem::class, 'product'));
    }

    public function test_order_item_has_product_variant_relationship_method(): void
    {
        $this->assertTrue(method_exists(OrderItem::class, 'productVariant'));
    }

    // ──────────────────────────────── OrderAddress ────────────────────────

    public function test_order_address_fillable_contains_expected_fields(): void
    {
        $address = new OrderAddress();

        $this->assertSame(
            ['customer_address_id', 'type', 'country', 'city', 'address_line_1', 'address_line_2', 'postcode'],
            $address->getFillable()
        );
    }

    public function test_order_address_has_order_relationship_method(): void
    {
        $this->assertTrue(method_exists(OrderAddress::class, 'order'));
    }

    public function test_order_address_casts_returns_array(): void
    {
        $this->assertIsArray((new OrderAddress())->getCasts());
    }
}
