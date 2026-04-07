<?php

namespace Tests\Feature\Api\Customer;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithCustomer(array $customerData = []): array
    {
        $customer = Customer::factory()->create(array_merge([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'phone'      => '1234567890',
        ], $customerData));

        return [$customer, $customer];
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

    private function validAddressPayload(array $overrides = []): array
    {
        return array_merge([
            'country'        => 'US',
            'city'           => 'New York',
            'address_line_1' => '123 Main St',
            'postcode'       => '10001',
        ], $overrides);
    }

    // ── GET /api/customers/me/addresses ──────────────────────────────────────

    public function test_list_addresses_requires_authentication(): void
    {
        $response = $this->getJson('/api/customers/me/addresses');

        $response->assertStatus(401);
    }

    public function test_list_addresses_returns_paginated_addresses(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $this->createAddress($customer);
        $this->createAddress($customer, ['city' => 'Los Angeles', 'postcode' => '90001']);

        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/customers/me/addresses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'country', 'city', 'address_line_1', 'postcode'],
                ],
                'meta',
                'links',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_list_addresses_returns_only_own_addresses(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        [$otherUser, $otherCustomer] = $this->createUserWithCustomer(['email' => 'other@example.com']);

        $this->createAddress($customer);
        $this->createAddress($otherCustomer, ['city' => 'Miami']);

        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/customers/me/addresses');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city', 'New York');
    }

    // ── POST /api/customers/me/addresses ─────────────────────────────────────

    public function test_create_address_requires_authentication(): void
    {
        $response = $this->postJson('/api/customers/me/addresses', $this->validAddressPayload());

        $response->assertStatus(401);
    }

    public function test_create_address_stores_new_address(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/customers/me/addresses', [
                'country'        => 'UK',
                'city'           => 'London',
                'address_line_1' => '10 Downing St',
                'address_line_2' => 'Flat 2',
                'postcode'       => 'SW1A 2AA',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.country', 'UK')
            ->assertJsonPath('data.city', 'London')
            ->assertJsonPath('data.address_line_1', '10 Downing St')
            ->assertJsonPath('data.address_line_2', 'Flat 2')
            ->assertJsonPath('data.postcode', 'SW1A 2AA');

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id'    => $customer->id,
            'country'        => 'UK',
            'city'           => 'London',
            'address_line_1' => '10 Downing St',
        ]);
    }

    public function test_create_address_address_line_2_is_optional(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/customers/me/addresses', $this->validAddressPayload());

        $response->assertStatus(201);
    }

    public function test_create_address_validates_required_fields(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/customers/me/addresses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country', 'city', 'address_line_1', 'postcode']);
    }

    public function test_create_address_validates_country_required(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/customers/me/addresses', $this->validAddressPayload(['country' => '']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    // ── GET /api/customers/me/addresses/{id} ─────────────────────────────────

    public function test_show_address_requires_authentication(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $address = $this->createAddress($customer);

        $response = $this->getJson("/api/customers/me/addresses/{$address->id}");

        $response->assertStatus(401);
    }

    public function test_show_address_returns_address_data(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $address = $this->createAddress($customer, [
            'country'        => 'UK',
            'city'           => 'London',
            'address_line_1' => '10 Downing St',
            'address_line_2' => 'Flat 2',
            'postcode'       => 'SW1A 2AA',
        ]);

        $response = $this->actingAs($user, 'customers')
            ->getJson("/api/customers/me/addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $address->id)
            ->assertJsonPath('data.country', 'UK')
            ->assertJsonPath('data.city', 'London')
            ->assertJsonPath('data.address_line_1', '10 Downing St')
            ->assertJsonPath('data.address_line_2', 'Flat 2')
            ->assertJsonPath('data.postcode', 'SW1A 2AA');
    }

    public function test_show_address_returns_403_for_other_customers_address(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        [$otherUser, $otherCustomer] = $this->createUserWithCustomer(['email' => 'other@example.com']);
        $otherAddress = $this->createAddress($otherCustomer, ['city' => 'Miami']);

        $response = $this->actingAs($user, 'customers')
            ->getJson("/api/customers/me/addresses/{$otherAddress->id}");

        $response->assertStatus(403);
    }

    public function test_show_address_returns_404_for_nonexistent_address(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/customers/me/addresses/99999');

        $response->assertStatus(404);
    }

    // ── PUT /api/customers/me/addresses/{id} ─────────────────────────────────

    public function test_update_address_requires_authentication(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $address = $this->createAddress($customer);

        $response = $this->putJson("/api/customers/me/addresses/{$address->id}", [
            'city' => 'Chicago',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_address_updates_address_data(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $address = $this->createAddress($customer);

        $response = $this->actingAs($user, 'customers')
            ->putJson("/api/customers/me/addresses/{$address->id}", [
                'city'    => 'Chicago',
                'postcode' => '60601',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.city', 'Chicago')
            ->assertJsonPath('data.postcode', '60601');

        $this->assertDatabaseHas('customer_addresses', [
            'id'      => $address->id,
            'city'    => 'Chicago',
            'postcode' => '60601',
        ]);
    }

    public function test_update_address_returns_403_for_other_customers_address(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        [$otherUser, $otherCustomer] = $this->createUserWithCustomer(['email' => 'other@example.com']);
        $otherAddress = $this->createAddress($otherCustomer, ['city' => 'Miami']);

        $response = $this->actingAs($user, 'customers')
            ->putJson("/api/customers/me/addresses/{$otherAddress->id}", [
                'city' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    // ── DELETE /api/customers/me/addresses/{id} ───────────────────────────────

    public function test_delete_address_requires_authentication(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $address = $this->createAddress($customer);

        $response = $this->deleteJson("/api/customers/me/addresses/{$address->id}");

        $response->assertStatus(401);
    }

    public function test_delete_address_removes_address(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $address = $this->createAddress($customer);

        $response = $this->actingAs($user, 'customers')
            ->deleteJson("/api/customers/me/addresses/{$address->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
    }

    public function test_delete_address_returns_403_for_other_customers_address(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        [$otherUser, $otherCustomer] = $this->createUserWithCustomer(['email' => 'other@example.com']);
        $otherAddress = $this->createAddress($otherCustomer);

        $response = $this->actingAs($user, 'customers')
            ->deleteJson("/api/customers/me/addresses/{$otherAddress->id}");

        $response->assertStatus(403);
    }

    public function test_delete_address_returns_404_for_nonexistent_address(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->deleteJson('/api/customers/me/addresses/99999');

        $response->assertStatus(404);
    }
}
