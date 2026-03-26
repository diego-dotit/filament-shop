<?php

namespace Tests\Unit\Models;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    // ──────────────────────────────── Cart ────────────────────────────────

    public function test_cart_fillable_contains_expected_fields(): void
    {
        $cart = new Cart();

        $this->assertSame(['customer_id'], $cart->getFillable());
    }

    public function test_cart_has_customer_relationship_method(): void
    {
        $this->assertTrue(method_exists(Cart::class, 'customer'));
    }

    public function test_cart_has_items_relationship_method(): void
    {
        $this->assertTrue(method_exists(Cart::class, 'items'));
    }

    public function test_cart_casts_returns_array(): void
    {
        $this->assertIsArray((new Cart())->getCasts());
    }

    // ──────────────────────────────── CartItem ────────────────────────────

    public function test_cart_item_fillable_contains_expected_fields(): void
    {
        $item = new CartItem();

        $this->assertSame(
            ['cart_id', 'product_id', 'product_variant_id', 'quantity'],
            $item->getFillable()
        );
    }

    public function test_cart_item_casts_quantity_as_integer(): void
    {
        $item = new CartItem();

        $this->assertArrayHasKey('quantity', $item->getCasts());
        $this->assertSame('integer', $item->getCasts()['quantity']);
    }

    public function test_cart_item_has_cart_relationship_method(): void
    {
        $this->assertTrue(method_exists(CartItem::class, 'cart'));
    }

    public function test_cart_item_has_product_relationship_method(): void
    {
        $this->assertTrue(method_exists(CartItem::class, 'product'));
    }

    public function test_cart_item_has_product_variant_relationship_method(): void
    {
        $this->assertTrue(method_exists(CartItem::class, 'productVariant'));
    }
}
