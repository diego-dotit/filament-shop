<?php

namespace Tests\Feature\Api\Customer;

use App\Domains\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // POST /customer/register
    // -----------------------------------------------------------------------

    public function test_successful_registration_returns_201_with_token(): void
    {
        $response = $this->postJson('/api/customer/register', [
            'first_name'            => 'Jane',
            'last_name'             => 'Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
                'message',
                'token',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Registration successful.');

        $this->assertDatabaseHas('customers', ['email' => 'jane@example.com']);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_duplicate_email_registration_returns_422(): void
    {
        Customer::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/customer/register', [
            'first_name'            => 'Jane',
            'last_name'             => 'Doe',
            'email'                 => 'existing@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // -----------------------------------------------------------------------
    // POST /customer/login
    // -----------------------------------------------------------------------

    public function test_successful_login_returns_200_with_token(): void
    {
        Customer::factory()->create([
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/customer/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
                'token',
            ])
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_invalid_credentials_login_returns_401(): void
    {
        Customer::factory()->create([
            'email'    => 'customer@example.com',
            'password' => 'correctpassword',
        ]);

        $response = $this->postJson('/api/customer/login', [
            'email'    => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'Invalid credentials.']);
    }

    // -----------------------------------------------------------------------
    // GET /customer/me
    // -----------------------------------------------------------------------

    public function test_authenticated_me_request_returns_200(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'customers')
            ->getJson('/api/customer/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $customer->email);
    }

    public function test_unauthenticated_me_request_returns_401(): void
    {
        $response = $this->getJson('/api/customer/me');

        $response->assertStatus(401);
    }
}
