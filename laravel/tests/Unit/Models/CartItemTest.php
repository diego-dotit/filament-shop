<?php

namespace Tests\Unit\Models;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class CartItemTest extends TestCase
{
    public function test_cart_item_class_exists(): void
    {
        $this->assertTrue(class_exists(CartItem::class));
    }

    public function test_cart_item_can_be_instantiated(): void
    {
        $item = new CartItem();

        $this->assertInstanceOf(CartItem::class, $item);
    }

    public function test_cart_item_fillable_contains_expected_fields(): void
    {
        $item = new CartItem();

        $this->assertContains('product_id', $item->getFillable());
        $this->assertContains('product_variant_id', $item->getFillable());
        $this->assertContains('quantity', $item->getFillable());
    }

    public function test_cart_item_quantity_cast_is_integer(): void
    {
        $item = new CartItem();

        $this->assertArrayHasKey('quantity', $item->getCasts());
        $this->assertSame('integer', $item->getCasts()['quantity']);
    }

    public function test_cart_item_cart_relationship_is_belongs_to(): void
    {
        $item = new CartItem();

        $this->assertInstanceOf(BelongsTo::class, $item->cart());
    }

    public function test_cart_item_cart_relationship_points_to_cart_model(): void
    {
        $item = new CartItem();

        $this->assertInstanceOf(Cart::class, $item->cart()->getRelated());
    }

    public function test_cart_item_product_relationship_is_belongs_to(): void
    {
        $item = new CartItem();

        $this->assertInstanceOf(BelongsTo::class, $item->product());
    }

    public function test_cart_item_product_relationship_points_to_product_model(): void
    {
        $item = new CartItem();

        $this->assertInstanceOf(Product::class, $item->product()->getRelated());
    }

    public function test_cart_item_product_variant_relationship_is_belongs_to(): void
    {
        $item = new CartItem();

        $this->assertInstanceOf(BelongsTo::class, $item->productVariant());
    }

    public function test_cart_item_product_variant_relationship_points_to_product_variant_model(): void
    {
        $item = new CartItem();

        $this->assertInstanceOf(ProductVariant::class, $item->productVariant()->getRelated());
    }

    public function test_cart_item_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass(CartItem::class);

        $this->assertSame('App\Domains\Cart\Models', $reflection->getNamespaceName());
    }
}
