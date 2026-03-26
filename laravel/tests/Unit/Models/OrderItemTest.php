<?php

namespace Tests\Unit\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function test_order_item_class_exists(): void
    {
        $this->assertTrue(class_exists(OrderItem::class));
    }

    public function test_order_item_can_be_instantiated(): void
    {
        $item = new OrderItem();

        $this->assertInstanceOf(OrderItem::class, $item);
    }

    public function test_order_item_snapshot_fields_are_in_fillable(): void
    {
        $item = new OrderItem();

        $fillable = $item->getFillable();

        $this->assertContains('product_name_snapshot', $fillable);
        $this->assertContains('variant_sku_snapshot', $fillable);
        $this->assertContains('unit_price_snapshot', $fillable);
        $this->assertContains('line_total_snapshot', $fillable);
    }

    public function test_order_item_snapshot_fields_are_not_live_relations(): void
    {
        $item = new OrderItem();

        // Snapshot fields are plain string/decimal columns — not relationship keys
        $this->assertArrayNotHasKey('product_name_snapshot', $item->getRelations());
        $this->assertArrayNotHasKey('variant_sku_snapshot', $item->getRelations());
    }

    public function test_order_item_unit_price_snapshot_cast_is_decimal(): void
    {
        $item = new OrderItem();

        $this->assertArrayHasKey('unit_price_snapshot', $item->getCasts());
        $this->assertStringStartsWith('decimal', $item->getCasts()['unit_price_snapshot']);
    }

    public function test_order_item_line_total_snapshot_cast_is_decimal(): void
    {
        $item = new OrderItem();

        $this->assertArrayHasKey('line_total_snapshot', $item->getCasts());
        $this->assertStringStartsWith('decimal', $item->getCasts()['line_total_snapshot']);
    }

    public function test_order_item_quantity_cast_is_integer(): void
    {
        $item = new OrderItem();

        $this->assertArrayHasKey('quantity', $item->getCasts());
        $this->assertSame('integer', $item->getCasts()['quantity']);
    }

    public function test_order_item_order_relationship_is_belongs_to(): void
    {
        $item = new OrderItem();

        $this->assertInstanceOf(BelongsTo::class, $item->order());
    }

    public function test_order_item_order_relationship_points_to_order_model(): void
    {
        $item = new OrderItem();

        $this->assertInstanceOf(Order::class, $item->order()->getRelated());
    }

    public function test_order_item_product_relationship_is_belongs_to(): void
    {
        $item = new OrderItem();

        $this->assertInstanceOf(BelongsTo::class, $item->product());
    }

    public function test_order_item_product_relationship_points_to_product_model(): void
    {
        $item = new OrderItem();

        $this->assertInstanceOf(Product::class, $item->product()->getRelated());
    }

    public function test_order_item_product_variant_relationship_is_belongs_to(): void
    {
        $item = new OrderItem();

        $this->assertInstanceOf(BelongsTo::class, $item->productVariant());
    }

    public function test_order_item_product_variant_relationship_points_to_product_variant_model(): void
    {
        $item = new OrderItem();

        $this->assertInstanceOf(ProductVariant::class, $item->productVariant()->getRelated());
    }
}
