<?php

namespace Tests\Feature\Api\Order;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Currency\Models\Currency;
use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Test Setup
    // -----------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure a base USD currency exists so the middleware (and service) can
        // resolve a Currency model even in a freshly-migrated test database.
        Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name'          => 'US Dollar',
                'symbol'        => '$',
                'exchange_rate' => '1.000000',
                'is_base'       => true,
            ],
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function createUserWithCustomer(array $customerData = []): array
    {
        $user     = User::factory()->create();
        $customer = $user->customer()->create(array_merge([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => $user->email,
            'phone'      => '1234567890',
        ], $customerData));

        return [$user, $customer];
    }

    private function createAddress(Customer $customer, array $data = []): CustomerAddress
    {
        return $customer->addresses()->create(array_merge([
            'country'        => 'US',
            'city'           => 'New York',
            'address_line_1' => '123 Main St',
            'address_line_2' => null,
            'postcode'       => '10001',
        ], $data));
    }

    private function createProductWithVariant(array $variantData = []): array
    {
        $product = Product::create([
            'name'      => ['en' => 'Test Product'],
            'slug'      => 'test-product-' . uniqid(),
            'is_active' => true,
        ]);

        $variant = $product->variants()->create(array_merge([
            'sku'            => 'TP-001-' . uniqid(),
            'regular_price'  => '25.00',
            'stock_quantity' => 10,
            'weight'         => 0.5,
            'is_active'      => true,
        ], $variantData));

        return [$product, $variant];
    }

    private function createCartWithItems(Customer $customer, int $itemCount = 1): Cart
    {
        $cart = $customer->cart()->create([]);

        for ($i = 0; $i < $itemCount; $i++) {
            [$product, $variant] = $this->createProductWithVariant([
                'regular_price' => '20.00',
                'sku'           => 'SKU-' . $i . '-' . uniqid(),
            ]);
            $cart->items()->create([
                'product_id'         => $product->id,
                'product_variant_id' => $variant->id,
                'quantity'           => 2,
            ]);
        }

        return $cart;
    }

    private function validOrderPayload(Customer $customer): array
    {
        $billing  = $this->createAddress($customer, ['city' => 'Billing City']);
        $shipping = $this->createAddress($customer, ['city' => 'Shipping City']);

        return [
            'billing_address_id'  => $billing->id,
            'shipping_address_id' => $shipping->id,
        ];
    }

    // -----------------------------------------------------------------------
    // POST /api/orders — authentication
    // -----------------------------------------------------------------------

    public function test_place_order_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders', [
            'billing_address_id'  => 1,
            'shipping_address_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    // -----------------------------------------------------------------------
    // POST /api/orders — empty cart rejection
    // -----------------------------------------------------------------------

    public function test_place_order_fails_if_cart_is_empty(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        // Create cart but no items
        $customer->cart()->create([]);
        $payload = $this->validOrderPayload($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cart']);
    }

    public function test_place_order_fails_if_no_cart_exists(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $payload = $this->validOrderPayload($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cart']);
    }

    // -----------------------------------------------------------------------
    // POST /api/orders — address ownership validation
    // -----------------------------------------------------------------------

    public function test_place_order_fails_if_billing_address_belongs_to_another_customer(): void
    {
        [$user, $customer]       = $this->createUserWithCustomer();
        [$otherUser, $otherCust] = $this->createUserWithCustomer(['email' => 'other@example.com']);

        $this->createCartWithItems($customer);
        $otherBilling  = $this->createAddress($otherCust, ['city' => 'OtherCity']);
        $ownShipping   = $this->createAddress($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'billing_address_id'  => $otherBilling->id,
                'shipping_address_id' => $ownShipping->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    public function test_place_order_fails_if_shipping_address_belongs_to_another_customer(): void
    {
        [$user, $customer]       = $this->createUserWithCustomer();
        [$otherUser, $otherCust] = $this->createUserWithCustomer(['email' => 'other@example.com']);

        $this->createCartWithItems($customer);
        $ownBilling    = $this->createAddress($customer);
        $otherShipping = $this->createAddress($otherCust, ['city' => 'OtherCity']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'billing_address_id'  => $ownBilling->id,
                'shipping_address_id' => $otherShipping->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    // -----------------------------------------------------------------------
    // POST /api/orders — insufficient stock
    // -----------------------------------------------------------------------

    public function test_place_order_fails_when_variant_has_insufficient_stock(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        // Create a variant with only 1 unit in stock
        [$product, $variant] = $this->createProductWithVariant([
            'stock_quantity' => 1,
            'regular_price'  => '10.00',
        ]);

        $cart = $customer->cart()->create([]);
        // Request quantity 5 but only 1 available
        $cart->items()->create([
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 5,
        ]);

        $payload = $this->validOrderPayload($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock']);
    }

    // -----------------------------------------------------------------------
    // POST /api/orders — successful order creation
    // -----------------------------------------------------------------------

    public function test_place_order_creates_order_with_correct_totals(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $cart = $this->createCartWithItems($customer, 1);
        $payload = $this->validOrderPayload($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'total_amount',
                    'items',
                    'billing_address',
                    'shipping_address',
                    'created_at',
                ],
            ]);

        // total: 1 item × qty 2 × price 20.00 = 40.00
        // PHP json_encode(40.0) → 40 (integer in JSON for whole numbers)
        $this->assertEquals(40.0, $response->json('data.total_amount'));
        $response->assertJsonPath('data.status', 'pending');
    }

    public function test_place_order_captures_immutable_snapshots(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        [$product, $variant] = $this->createProductWithVariant([
            'sku'           => 'SNAP-001',
            'regular_price' => '15.00',
        ]);

        $cart = $customer->cart()->create([]);
        $cart->items()->create([
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 3,
        ]);

        $payload = $this->validOrderPayload($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        $orderId = $response->json('data.id');
        $order   = Order::with('items')->find($orderId);

        $this->assertCount(1, $order->items);
        $item = $order->items->first();

        $this->assertSame('Test Product', $item->product_name_snapshot);
        $this->assertSame('SNAP-001', $item->variant_sku_snapshot);
        $this->assertEquals('15.00', $item->unit_price_snapshot);
        $this->assertSame(3, $item->quantity);
        $this->assertEquals('45.00', $item->line_total_snapshot);
    }

    public function test_place_order_captures_address_snapshots(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $this->createCartWithItems($customer);

        $billing = $this->createAddress($customer, [
            'country'        => 'GB',
            'city'           => 'London',
            'address_line_1' => '10 Downing St',
            'postcode'       => 'SW1A 2AA',
        ]);
        $shipping = $this->createAddress($customer, [
            'country'        => 'US',
            'city'           => 'New York',
            'address_line_1' => '1600 Penn Ave',
            'postcode'       => '20500',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'billing_address_id'  => $billing->id,
                'shipping_address_id' => $shipping->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.billing_address.country', 'GB')
            ->assertJsonPath('data.billing_address.city', 'London')
            ->assertJsonPath('data.billing_address.type', 'billing')
            ->assertJsonPath('data.shipping_address.country', 'US')
            ->assertJsonPath('data.shipping_address.city', 'New York')
            ->assertJsonPath('data.shipping_address.type', 'shipping');
    }

    public function test_place_order_clears_cart_after_success(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $cart    = $this->createCartWithItems($customer, 2);
        $payload = $this->validOrderPayload($customer);

        $this->assertSame(2, $cart->items()->count());

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload)
            ->assertStatus(201);

        $this->assertSame(0, $cart->fresh()->items()->count());
    }

    public function test_place_order_cart_is_empty_via_api_after_success(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $this->createCartWithItems($customer, 1);
        $payload = $this->validOrderPayload($customer);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload)
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/cart')
            ->assertStatus(200)
            ->assertJsonPath('data.items', []);
    }

    public function test_place_order_address_snapshot_is_immutable(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $this->createCartWithItems($customer);

        $billing = $this->createAddress($customer, [
            'country'        => 'US',
            'city'           => 'Original City',
            'address_line_1' => '100 Original St',
            'postcode'       => '10001',
        ]);
        $shipping = $this->createAddress($customer, [
            'country'        => 'US',
            'city'           => 'Original Ship City',
            'address_line_1' => '200 Original Ave',
            'postcode'       => '20002',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'billing_address_id'  => $billing->id,
                'shipping_address_id' => $shipping->id,
            ]);

        $response->assertStatus(201);
        $orderId = $response->json('data.id');

        // Mutate the CustomerAddress records after the order was placed
        $billing->update([
            'city'           => 'Changed City',
            'address_line_1' => '999 Changed St',
        ]);
        $shipping->update([
            'city' => 'Changed Ship City',
        ]);

        // The order snapshots must still reflect the original values
        $order = \App\Domains\Order\Models\Order::with('addresses')->find($orderId);

        $billingSnapshot  = $order->addresses->firstWhere('type', 'billing');
        $shippingSnapshot = $order->addresses->firstWhere('type', 'shipping');

        $this->assertSame('Original City', $billingSnapshot->city);
        $this->assertSame('100 Original St', $billingSnapshot->address_line_1);
        $this->assertSame('Original Ship City', $shippingSnapshot->city);
    }

    public function test_place_order_stores_currency_snapshot(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $this->createCartWithItems($customer);
        $payload = $this->validOrderPayload($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'id'            => $response->json('data.id'),
            'customer_id'   => $customer->id,
            'currency_code' => 'USD',
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/orders — missing base currency
    // -----------------------------------------------------------------------

    public function test_place_order_fails_gracefully_when_no_base_currency_is_configured(): void
    {
        // Delete all currencies so the middleware resolves null
        Currency::query()->delete();

        [$user, $customer] = $this->createUserWithCustomer();
        $this->createCartWithItems($customer);
        $payload = $this->validOrderPayload($customer);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'error'   => 'no_base_currency',
            ])
            ->assertJsonPath('message', 'No base currency is configured. Please contact support.');
    }

    // -----------------------------------------------------------------------
    // GET /api/orders — list orders
    // -----------------------------------------------------------------------

    public function test_list_orders_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    public function test_list_orders_returns_paginated_orders_for_authenticated_customer(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        // Create 2 orders directly in DB
        $customer->orders()->create([
            'status'        => 'pending',
            'total_amount'  => '50.00',
            'currency_code' => 'USD',
            'exchange_rate' => '1.000000',
        ]);
        $customer->orders()->create([
            'status'        => 'pending',
            'total_amount'  => '75.00',
            'currency_code' => 'USD',
            'exchange_rate' => '1.000000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'status', 'total_amount', 'created_at'],
                ],
                'meta',
                'links',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_list_orders_returns_only_own_orders(): void
    {
        [$user, $customer]       = $this->createUserWithCustomer();
        [$otherUser, $otherCust] = $this->createUserWithCustomer(['email' => 'other@example.com']);

        $customer->orders()->create([
            'status'        => 'pending',
            'total_amount'  => '50.00',
            'currency_code' => 'USD',
            'exchange_rate' => '1.000000',
        ]);
        $otherCust->orders()->create([
            'status'        => 'pending',
            'total_amount'  => '99.00',
            'currency_code' => 'USD',
            'exchange_rate' => '1.000000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // -----------------------------------------------------------------------
    // GET /api/orders/{id} — single order
    // -----------------------------------------------------------------------

    public function test_show_order_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders/1');

        $response->assertStatus(401);
    }

    public function test_show_order_returns_order_detail_with_items_and_addresses(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $order = $customer->orders()->create([
            'status'        => 'pending',
            'total_amount'  => '30.00',
            'currency_code' => 'USD',
            'exchange_rate' => '1.000000',
        ]);

        [$product, $variant] = $this->createProductWithVariant();
        $order->items()->create([
            'product_id'             => $product->id,
            'product_variant_id'     => $variant->id,
            'product_name_snapshot'  => 'Test Product',
            'variant_sku_snapshot'   => 'TP-001',
            'unit_price_snapshot'    => '15.00',
            'quantity'               => 2,
            'line_total_snapshot'    => '30.00',
        ]);

        $order->addresses()->create([
            'type'           => 'billing',
            'country'        => 'US',
            'city'           => 'NYC',
            'address_line_1' => '100 Main St',
            'postcode'       => '10001',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'total_amount',
                    'items' => [
                        '*' => [
                            'product_name_snapshot',
                            'variant_sku_snapshot',
                            'unit_price_snapshot',
                            'quantity',
                            'line_total_snapshot',
                        ],
                    ],
                    'billing_address',
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.items.0.product_name_snapshot', 'Test Product');
    }

    public function test_show_order_returns_403_for_another_customers_order(): void
    {
        [$user, $customer]       = $this->createUserWithCustomer();
        [$otherUser, $otherCust] = $this->createUserWithCustomer(['email' => 'other@example.com']);

        $otherOrder = $otherCust->orders()->create([
            'status'        => 'pending',
            'total_amount'  => '99.00',
            'currency_code' => 'USD',
            'exchange_rate' => '1.000000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$otherOrder->id}");

        $response->assertStatus(403);
    }

    public function test_show_order_returns_404_for_nonexistent_order(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders/99999');

        $response->assertStatus(404);
    }
}
