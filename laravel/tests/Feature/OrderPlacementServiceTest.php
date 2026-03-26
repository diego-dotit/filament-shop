<?php

namespace Tests\Feature;

use App\Domains\Cart\Models\Cart;
use App\Domains\CartProduct\Exceptions\InsufficientStockException;
use App\Domains\Currency\Models\Currency;
use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\CustomerOrder\CustomerOrderConnectorService;
use App\Domains\CustomerOrder\Exceptions\UnauthorizedAddressException;
use App\Domains\Order\Models\Order;
use App\Domains\OrderPlacement\Exceptions\EmptyCartException;
use App\Domains\OrderPlacement\OrderPlacementService;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPlacementServiceTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeService(): OrderPlacementService
    {
        return app(OrderPlacementService::class);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $user = User::factory()->create();

        return $user->customer()->create(array_merge([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => $user->email,
            'phone'      => '5550001111',
        ], $overrides));
    }

    private function createAddress(Customer $customer, array $overrides = []): CustomerAddress
    {
        return $customer->addresses()->create(array_merge([
            'country'        => 'US',
            'city'           => 'Springfield',
            'address_line_1' => '742 Evergreen Terrace',
            'address_line_2' => null,
            'postcode'       => '62701',
        ], $overrides));
    }

    private function createProductWithVariant(array $variantOverrides = []): array
    {
        $product = Product::create([
            'name'      => ['en' => 'Service Test Product'],
            'slug'      => 'svc-test-product-' . uniqid(),
            'is_active' => true,
        ]);

        $variant = $product->variants()->create(array_merge([
            'sku'            => 'SVC-SKU-' . uniqid(),
            'regular_price'  => '10.00',
            'stock_quantity' => 100,
            'weight'         => 0.5,
            'is_active'      => true,
        ], $variantOverrides));

        return [$product, $variant];
    }

    private function createCartWithItems(Customer $customer, int $quantity = 2): Cart
    {
        [$product, $variant] = $this->createProductWithVariant();

        $cart = $customer->cart()->create([]);
        $cart->items()->create([
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => $quantity,
        ]);

        return $cart->load('items.productVariant.product');
    }

    private function createBaseCurrency(): Currency
    {
        return Currency::factory()->base()->create(['code' => 'USD']);
    }

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    public function test_place_order_returns_persisted_order_with_all_relationships(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer, ['city' => 'Billing City']);
        $shipping = $this->createAddress($customer, ['city' => 'Shipping City']);
        $currency = $this->createBaseCurrency();
        $this->createCartWithItems($customer, 3);

        $service = $this->makeService();
        $order   = $service->placeOrder($customer, $billing, $shipping, $currency);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->id);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_place_order_calculates_correct_total_amount(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();

        // 1 item × qty 3 × price 10.00 = 30.00
        $this->createCartWithItems($customer, 3);

        $order = $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);

        $this->assertEquals('30.00', $order->total_amount);
    }

    public function test_place_order_captures_currency_snapshot(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();
        $this->createCartWithItems($customer);

        $order = $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);

        $this->assertSame('USD', $order->currency_code);
        $this->assertNotNull($order->exchange_rate);
    }

    public function test_place_order_saves_order_items(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();
        $this->createCartWithItems($customer, 2);

        $order = $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);

        $this->assertCount(1, $order->items);
        $item = $order->items->first();
        $this->assertSame(2, $item->quantity);
        $this->assertSame('Service Test Product', $item->product_name_snapshot);
        $this->assertEquals('20.00', $item->line_total_snapshot);
    }

    public function test_place_order_attaches_address_snapshots(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer, ['city' => 'BillingTown']);
        $shipping = $this->createAddress($customer, ['city' => 'ShippingTown']);
        $currency = $this->createBaseCurrency();
        $this->createCartWithItems($customer);

        $order = $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);

        $this->assertCount(2, $order->addresses);

        $billingAddr  = $order->addresses->firstWhere('type', 'billing');
        $shippingAddr = $order->addresses->firstWhere('type', 'shipping');

        $this->assertNotNull($billingAddr);
        $this->assertSame('BillingTown', $billingAddr->city);

        $this->assertNotNull($shippingAddr);
        $this->assertSame('ShippingTown', $shippingAddr->city);
    }

    public function test_place_order_clears_cart_after_success(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();
        $cart     = $this->createCartWithItems($customer, 2);

        $this->assertSame(1, $cart->items()->count());

        $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);

        $this->assertSame(0, $cart->fresh()->items()->count());
    }

    public function test_place_order_loads_relationships_on_returned_order(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();
        $this->createCartWithItems($customer);

        $order = $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);

        $this->assertTrue($order->relationLoaded('items'));
        $this->assertTrue($order->relationLoaded('addresses'));
        $this->assertTrue($order->relationLoaded('customer'));
    }

    // -----------------------------------------------------------------------
    // Empty cart exceptions
    // -----------------------------------------------------------------------

    public function test_place_order_throws_empty_cart_exception_when_no_cart_exists(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();

        $this->expectException(EmptyCartException::class);

        $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);
    }

    public function test_place_order_throws_empty_cart_exception_when_cart_has_no_items(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();

        // Create empty cart (no items)
        $customer->cart()->create([]);

        $this->expectException(EmptyCartException::class);

        $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);
    }

    // -----------------------------------------------------------------------
    // Exception propagation
    // -----------------------------------------------------------------------

    public function test_place_order_propagates_insufficient_stock_exception(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();

        // Variant with 0 stock but 2 in cart
        [$product, $variant] = $this->createProductWithVariant(['stock_quantity' => 0]);
        $cart = $customer->cart()->create([]);
        $cart->items()->create([
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        $this->expectException(InsufficientStockException::class);

        $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);
    }

    public function test_place_order_propagates_unauthorized_address_exception(): void
    {
        $customer      = $this->createCustomer();
        $otherUser     = User::factory()->create();
        $otherCustomer = $otherUser->customer()->create([
            'first_name' => 'Other',
            'last_name'  => 'Guy',
            'email'      => $otherUser->email,
            'phone'      => '9990001111',
        ]);

        $billing        = $this->createAddress($otherCustomer); // belongs to other customer
        $shipping       = $this->createAddress($customer);
        $currency       = $this->createBaseCurrency();

        $this->createCartWithItems($customer);

        $this->expectException(UnauthorizedAddressException::class);

        $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);
    }

    // -----------------------------------------------------------------------
    // Atomicity / transaction rollback
    // -----------------------------------------------------------------------

    public function test_exception_mid_transaction_rolls_back_order_and_leaves_cart_intact(): void
    {
        $customer = $this->createCustomer();
        $billing  = $this->createAddress($customer);
        $shipping = $this->createAddress($customer);
        $currency = $this->createBaseCurrency();
        $cart     = $this->createCartWithItems($customer, 2);

        // Force an exception inside the transaction after Order::create() has run,
        // by making CustomerOrderConnectorService throw a RuntimeException.
        $this->mock(CustomerOrderConnectorService::class)
            ->shouldReceive('attachCustomerAndAddressesToOrder')
            ->once()
            ->andThrow(new \RuntimeException('Simulated mid-transaction failure'));

        try {
            $this->makeService()->placeOrder($customer, $billing, $shipping, $currency);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            // Exception propagated correctly — now verify the transaction rolled back
            $this->assertDatabaseMissing('orders', ['customer_id' => $customer->id]);
            $this->assertSame(1, $cart->fresh()->items()->count());
        }
    }
}
