<?php

namespace Tests\Feature\Api\Cart;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function createUserWithCustomer(): array
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'phone'      => '1234567890',
        ]);

        return [$customer, $customer];
    }

    private function createActiveVariant(int $stock = 10, float $price = 19.99): ProductVariant
    {
        $product = Product::create([
            'name'        => 'Test Product ' . uniqid(),
            'slug'        => 'test-product-' . uniqid(),
            'description' => 'A test product',
            'is_active'   => true,
        ]);

        return $product->variants()->create([
            'sku'            => 'SKU-' . uniqid(),
            'regular_price'  => $price,
            'stock_quantity' => $stock,
            'weight'         => 1.0,
            'is_active'      => true,
        ]);
    }

    private function createInactiveVariant(): ProductVariant
    {
        $product = Product::create([
            'name'      => 'Inactive Product ' . uniqid(),
            'slug'      => 'inactive-product-' . uniqid(),
            'is_active' => true,
        ]);

        return $product->variants()->create([
            'sku'            => 'INACTIVE-' . uniqid(),
            'regular_price'  => 9.99,
            'stock_quantity' => 5,
            'weight'         => 1.0,
            'is_active'      => false,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /cart
    // -----------------------------------------------------------------------

    public function test_get_cart_requires_authentication(): void
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }

    public function test_get_cart_returns_empty_structure_when_no_cart_exists(): void
    {
        [$user] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total', '0.00');
    }

    public function test_get_cart_returns_cart_with_items(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variant           = $this->createActiveVariant(10, 19.99);
        $cart              = Cart::create(['customer_id' => $customer->id]);
        $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'items' => [['id', 'quantity', 'line_total']],
                    'total',
                ],
            ])
            ->assertJsonPath('data.id', $cart->id);
    }

    // -----------------------------------------------------------------------
    // POST /cart/items
    // -----------------------------------------------------------------------

    public function test_add_item_requires_authentication(): void
    {
        $response = $this->postJson('/api/cart/items', [
            'product_variant_id' => 1,
            'quantity'           => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_add_item_creates_cart_if_none_exists(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variant           = $this->createActiveVariant();

        $this->assertNull($customer->cart);

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity'           => 1,
            ]);

        $response->assertSuccessful()
            ->assertJsonStructure(['data' => ['id', 'items', 'total']]);

        $this->assertDatabaseHas('carts', ['customer_id' => $customer->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);
    }

    public function test_add_item_increments_quantity_when_variant_already_in_cart(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variant           = $this->createActiveVariant(20);
        $cart              = Cart::create(['customer_id' => $customer->id]);
        $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 3,
        ]);

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity'           => 2,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cart_items', [
            'cart_id'            => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 5,
        ]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_add_item_rejects_inactive_variant(): void
    {
        [$user] = $this->createUserWithCustomer();
        $variant = $this->createInactiveVariant();

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity'           => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_add_item_rejects_when_quantity_exceeds_stock(): void
    {
        [$user] = $this->createUserWithCustomer();
        $variant = $this->createActiveVariant(stock: 3);

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity'           => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_add_item_rejects_when_combined_quantity_exceeds_stock(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variant           = $this->createActiveVariant(stock: 5);
        $cart              = Cart::create(['customer_id' => $customer->id]);
        $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 4,
        ]);

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity'           => 3,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    // -----------------------------------------------------------------------
    // PUT /cart/items/{id}
    // -----------------------------------------------------------------------

    public function test_update_item_requires_authentication(): void
    {
        $response = $this->putJson('/api/cart/items/1', ['quantity' => 2]);

        $response->assertStatus(401);
    }

    public function test_update_item_quantity(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variant           = $this->createActiveVariant(10);
        $cart              = Cart::create(['customer_id' => $customer->id]);
        $item              = $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);

        $response = $this->actingAs($user, 'customers')
            ->putJson("/api/cart/items/{$item->id}", ['quantity' => 4]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'items', 'total']]);

        $this->assertDatabaseHas('cart_items', [
            'id'       => $item->id,
            'quantity' => 4,
        ]);
    }

    public function test_update_item_returns_403_for_another_customers_item(): void
    {
        // attacker
        [$attacker] = $this->createUserWithCustomer();

        // victim's item
        [$victim, $victimCustomer] = $this->createUserWithCustomer();
        $variant                   = $this->createActiveVariant(10);
        $cart                      = Cart::create(['customer_id' => $victimCustomer->id]);
        $item                      = $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);

        $response = $this->actingAs($attacker, 'customers')
            ->putJson("/api/cart/items/{$item->id}", ['quantity' => 99]);

        $response->assertStatus(403);
    }

    public function test_update_item_removes_item_when_quantity_is_zero(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variant           = $this->createActiveVariant(10);
        $cart              = Cart::create(['customer_id' => $customer->id]);
        $item              = $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 3,
        ]);

        $response = $this->actingAs($user, 'customers')
            ->putJson("/api/cart/items/{$item->id}", ['quantity' => 0]);

        $response->assertStatus(200)
            ->assertJsonPath('data.items', []);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    // -----------------------------------------------------------------------
    // DELETE /cart/items/{id}
    // -----------------------------------------------------------------------

    public function test_delete_item_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/cart/items/1');

        $response->assertStatus(401);
    }

    public function test_delete_item_removes_from_cart(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variant           = $this->createActiveVariant();
        $cart              = Cart::create(['customer_id' => $customer->id]);
        $item              = $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        $response = $this->actingAs($user, 'customers')
            ->deleteJson("/api/cart/items/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.items', []);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_delete_item_returns_403_for_another_customers_item(): void
    {
        [$attacker] = $this->createUserWithCustomer();

        [$victim, $victimCustomer] = $this->createUserWithCustomer();
        $variant                   = $this->createActiveVariant();
        $cart                      = Cart::create(['customer_id' => $victimCustomer->id]);
        $item                      = $cart->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);

        $response = $this->actingAs($attacker, 'customers')
            ->deleteJson("/api/cart/items/{$item->id}");

        $response->assertStatus(403);
    }

    // -----------------------------------------------------------------------
    // Cart total calculation
    // -----------------------------------------------------------------------

    public function test_cart_total_is_sum_of_line_totals(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $variantA          = $this->createActiveVariant(10, 10.00);
        $variantB          = $this->createActiveVariant(10, 5.50);
        $cart              = Cart::create(['customer_id' => $customer->id]);

        // 2 × $10.00 = $20.00
        $cart->items()->create([
            'product_id'         => $variantA->product_id,
            'product_variant_id' => $variantA->id,
            'quantity'           => 2,
        ]);

        // 3 × $5.50 = $16.50
        $cart->items()->create([
            'product_id'         => $variantB->product_id,
            'product_variant_id' => $variantB->id,
            'quantity'           => 3,
        ]);

        // Expected total: $20.00 + $16.50 = $36.50
        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 36.5);
    }
}
