<?php

namespace Tests\Feature;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Category\Models\Category;
use App\Domains\Currency\Models\Currency;
use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Attribute;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductAttribute;
use App\Domains\Product\Models\ProductVariant;
use App\Domains\Product\Models\ProductVariantAttribute;
use App\Domains\Review\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Customer & CustomerAddress
    // -----------------------------------------------------------------------

    public function test_customer_factory_creates_record(): void
    {
        $customer = Customer::factory()->create();

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertNotEmpty($customer->first_name);
        $this->assertNotEmpty($customer->email);
    }

    public function test_customer_factory_make_does_not_persist(): void
    {
        $customer = Customer::factory()->make();

        $this->assertNotNull($customer->first_name);
        $this->assertNull($customer->id);
    }

    public function test_customer_address_factory_creates_record(): void
    {
        $address = CustomerAddress::factory()->create();

        $this->assertDatabaseHas('customer_addresses', ['id' => $address->id]);
        $this->assertNotEmpty($address->country);
        $this->assertNotNull($address->customer_id);
    }

    // -----------------------------------------------------------------------
    // Product & ProductVariant
    // -----------------------------------------------------------------------

    public function test_product_factory_creates_record(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertNotEmpty($product->slug);
    }

    public function test_product_factory_active_state(): void
    {
        $product = Product::factory()->active()->create();

        $this->assertTrue($product->is_active);
    }

    public function test_product_factory_inactive_state(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->assertFalse($product->is_active);
    }

    public function test_product_factory_creates_five_records(): void
    {
        Product::factory(5)->create();

        $this->assertDatabaseCount('products', 5);
    }

    public function test_product_variant_factory_creates_record(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
        $this->assertNotNull($variant->product_id);
        $this->assertNotEmpty($variant->sku);
    }

    public function test_product_variant_active_state(): void
    {
        $variant = ProductVariant::factory()->active()->create();

        $this->assertTrue($variant->is_active);
    }

    // -----------------------------------------------------------------------
    // Attribute, ProductAttribute, ProductVariantAttribute
    // -----------------------------------------------------------------------

    public function test_attribute_factory_creates_record(): void
    {
        $attribute = Attribute::factory()->create();

        $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
        $this->assertNotEmpty($attribute->name);
    }

    public function test_product_attribute_factory_creates_record(): void
    {
        $pa = ProductAttribute::factory()->create();

        $this->assertDatabaseHas('product_attributes', ['id' => $pa->id]);
        $this->assertNotNull($pa->product_id);
        $this->assertNotNull($pa->attribute_id);
    }

    public function test_product_variant_attribute_factory_creates_record(): void
    {
        $pva = ProductVariantAttribute::factory()->create();

        $this->assertDatabaseHas('product_variant_attributes', ['id' => $pva->id]);
        $this->assertNotNull($pva->product_variant_id);
    }

    // -----------------------------------------------------------------------
    // Category
    // -----------------------------------------------------------------------

    public function test_category_factory_creates_record(): void
    {
        $category = Category::factory()->create();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertNotEmpty($category->slug);
    }

    public function test_category_factory_active_state(): void
    {
        $category = Category::factory()->active()->create();

        $this->assertTrue($category->is_active);
    }

    // -----------------------------------------------------------------------
    // Manufacturer
    // -----------------------------------------------------------------------

    public function test_manufacturer_factory_creates_record(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        $this->assertDatabaseHas('manufacturers', ['id' => $manufacturer->id]);
        $this->assertNotEmpty($manufacturer->name);
    }

    // -----------------------------------------------------------------------
    // Cart & CartItem
    // -----------------------------------------------------------------------

    public function test_cart_factory_creates_record(): void
    {
        $cart = Cart::factory()->create();

        $this->assertDatabaseHas('carts', ['id' => $cart->id]);
        $this->assertNotNull($cart->customer_id);
    }

    public function test_cart_item_factory_creates_record(): void
    {
        $item = CartItem::factory()->create();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        $this->assertNotNull($item->cart_id);
        $this->assertGreaterThan(0, $item->quantity);
    }

    // -----------------------------------------------------------------------
    // Order, OrderItem, OrderAddress
    // -----------------------------------------------------------------------

    public function test_order_factory_creates_record(): void
    {
        $order = Order::factory()->create();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertNotNull($order->customer_id);
        $this->assertNotEmpty($order->status);
    }

    public function test_order_factory_pending_state(): void
    {
        $order = Order::factory()->pending()->create();

        $this->assertEquals('pending', $order->status);
    }

    public function test_order_factory_completed_state(): void
    {
        $order = Order::factory()->completed()->create();

        $this->assertEquals('completed', $order->status);
    }

    public function test_order_item_factory_creates_record(): void
    {
        $item = OrderItem::factory()->create();

        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
        $this->assertNotNull($item->order_id);
        $this->assertGreaterThan(0, $item->quantity);
    }

    public function test_order_address_factory_creates_record(): void
    {
        $address = OrderAddress::factory()->create();

        $this->assertDatabaseHas('order_addresses', ['id' => $address->id]);
        $this->assertNotNull($address->order_id);
        $this->assertNotEmpty($address->country);
    }

    // -----------------------------------------------------------------------
    // Review
    // -----------------------------------------------------------------------

    public function test_review_factory_creates_record(): void
    {
        $review = Review::factory()->create();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
        $this->assertNotNull($review->product_id);
        $this->assertNotNull($review->customer_id);
        $this->assertGreaterThanOrEqual(1, $review->rating);
        $this->assertLessThanOrEqual(5, $review->rating);
    }

    public function test_review_factory_approved_state(): void
    {
        $review = Review::factory()->approved()->create();

        $this->assertEquals('approved', $review->status);
    }

    public function test_review_factory_pending_state(): void
    {
        $review = Review::factory()->pending()->create();

        $this->assertEquals('pending', $review->status);
    }

    // -----------------------------------------------------------------------
    // Language & Currency
    // -----------------------------------------------------------------------

    public function test_language_factory_creates_record(): void
    {
        $language = Language::factory()->create();

        $this->assertDatabaseHas('languages', ['id' => $language->id]);
        $this->assertNotEmpty($language->code);
        $this->assertNotEmpty($language->name);
    }

    public function test_language_factory_default_state(): void
    {
        $language = Language::factory()->default()->create();

        $this->assertTrue($language->is_default);
    }

    public function test_currency_factory_creates_record(): void
    {
        $currency = Currency::factory()->create();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
        $this->assertNotEmpty($currency->code);
        $this->assertNotEmpty($currency->symbol);
    }

    public function test_currency_factory_base_state(): void
    {
        $currency = Currency::factory()->base()->create();

        $this->assertTrue($currency->is_base);
    }
}
