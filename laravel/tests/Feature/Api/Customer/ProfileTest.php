<?php

namespace Tests\Feature\Api\Customer;

use App\Domains\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    // ── GET /api/customers/me ─────────────────────────────────────────────────

    public function test_get_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/customers/me');

        $response->assertStatus(401);
    }

    public function test_get_profile_returns_customer_data(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/customers/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'first_name', 'last_name', 'email', 'phone'],
            ])
            ->assertJsonPath('data.first_name', 'John')
            ->assertJsonPath('data.last_name', 'Doe')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.phone', '1234567890');
    }

    public function test_get_profile_returns_all_fields(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->getJson('/api/customers/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at', 'updated_at'],
            ]);
    }

    // ── PUT /api/customers/me ─────────────────────────────────────────────────

    public function test_update_profile_requires_authentication(): void
    {
        $response = $this->putJson('/api/customers/me', [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_profile_updates_customer_data(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->putJson('/api/customers/me', [
                'first_name' => 'Jane',
                'last_name'  => 'Smith',
                'email'      => 'jane@example.com',
                'phone'      => '0987654321',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.last_name', 'Smith')
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.phone', '0987654321');

        $this->assertDatabaseHas('customers', [
            'id'         => $customer->id,
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
            'email'      => 'jane@example.com',
        ]);
    }

    public function test_update_profile_partial_update(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->putJson('/api/customers/me', [
                'first_name' => 'UpdatedName',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', 'UpdatedName')
            ->assertJsonPath('data.last_name', 'Doe');
    }

    public function test_update_profile_returns_validation_error_for_invalid_email(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->putJson('/api/customers/me', [
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
