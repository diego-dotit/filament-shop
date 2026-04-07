<?php

namespace Tests\Unit\Domains\CartProduct;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\CartProduct\CartProductConnectorService;
use App\Domains\CartProduct\Exceptions\InactiveVariantException;
use App\Domains\CartProduct\Exceptions\InsufficientStockException;
use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartProductConnectorServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartProductConnectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CartProductConnectorService();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function createCustomer(): Customer
    {
        return Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'phone'      => '0000000000',
        ]);
    }

    private function createActiveVariant(int $stock = 10): ProductVariant
    {
        $product = Product::create([
            'name'        => 'Product ' . uniqid(),
            'slug'        => 'product-' . uniqid(),
            'description' => 'desc',
            'is_active'   => true,
        ]);

        return $product->variants()->create([
            'sku'            => 'SKU-' . uniqid(),
            'regular_price'  => 9.99,
            'stock_quantity' => $stock,
            'weight'         => 1.0,
            'is_active'      => true,
        ]);
    }

    private function createInactiveVariant(): ProductVariant
    {
        $product = Product::create([
            'name'      => 'Inactive ' . uniqid(),
            'slug'      => 'inactive-' . uniqid(),
            'is_active' => true,
        ]);

        return $product->variants()->create([
            'sku'            => 'INACTIVE-' . uniqid(),
            'regular_price'  => 9.99,
            'stock_quantity' => 10,
            'weight'         => 1.0,
            'is_active'      => false,
        ]);
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    public function test_add_product_variant_creates_new_cart_and_item_when_none_exist(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createActiveVariant(stock: 10);

        $this->assertNull($customer->cart);

        $cart = $this->service->addProductVariantToCart($customer, $variant, 2);

        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertDatabaseHas('carts', ['customer_id' => $customer->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);
        $this->assertTrue($cart->relationLoaded('items'));
        $this->assertCount(1, $cart->items);
    }

    public function test_add_product_variant_increments_quantity_when_variant_already_in_cart(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createActiveVariant(stock: 20);
        $cart     = Cart::create(['customer_id' => $customer->id]);
        $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 3,
        ]);

        $result = $this->service->addProductVariantToCart($customer, $variant, 4);

        $this->assertDatabaseHas('cart_items', [
            'cart_id'            => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 7,
        ]);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertCount(1, $result->items);
    }

    public function test_add_product_variant_throws_insufficient_stock_exception_when_stock_unavailable(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createActiveVariant(stock: 3);

        $this->expectException(InsufficientStockException::class);

        $this->service->addProductVariantToCart($customer, $variant, 5);
    }

    public function test_insufficient_stock_exception_has_clear_message(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createActiveVariant(stock: 2);

        try {
            $this->service->addProductVariantToCart($customer, $variant, 10);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsStringIgnoringCase('stock', $e->getMessage());
        }
    }

    public function test_add_product_variant_throws_inactive_variant_exception_for_inactive_variant(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createInactiveVariant();

        $this->expectException(InactiveVariantException::class);

        $this->service->addProductVariantToCart($customer, $variant, 1);
    }

    public function test_add_product_variant_uses_existing_cart_when_one_exists(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createActiveVariant(stock: 10);
        $existing = Cart::create(['customer_id' => $customer->id]);

        $result = $this->service->addProductVariantToCart($customer, $variant, 1);

        $this->assertEquals($existing->id, $result->id);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_stock_not_decremented_at_cart_add_time(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createActiveVariant(stock: 10);

        $this->service->addProductVariantToCart($customer, $variant, 3);

        $this->assertDatabaseHas('product_variants', [
            'id'             => $variant->id,
            'stock_quantity' => 10,
        ]);
    }

    public function test_transaction_rolls_back_cart_creation_when_insufficient_stock_exception_is_thrown(): void
    {
        $customer = $this->createCustomer();
        $variant  = $this->createActiveVariant(stock: 2);

        // Customer has no cart yet; the transaction should create one then roll it back
        $this->assertNull($customer->cart);

        try {
            $this->service->addProductVariantToCart($customer, $variant, 5);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException) {
            // Exception caught — verify no partial state was persisted
            $this->assertDatabaseMissing('carts', ['customer_id' => $customer->id]);
            $this->assertDatabaseCount('cart_items', 0);
        }
    }
}
