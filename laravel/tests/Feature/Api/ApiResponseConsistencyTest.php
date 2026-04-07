<?php

namespace Tests\Feature\Api;

use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that API endpoints consistently include the `success` field in their
 * JSON responses after the ApiResponse helper is applied to controllers.
 */
class ApiResponseConsistencyTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Auth endpoints
    // -----------------------------------------------------------------------

    public function test_register_response_includes_success_true(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Registration successful.');
    }

    public function test_login_success_response_includes_success_true(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Token must still be accessible at top level
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_failure_response_includes_success_false(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('correct'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'invalid_credentials');
    }

    public function test_me_endpoint_response_includes_success_true(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // -----------------------------------------------------------------------
    // Customer profile endpoint
    // -----------------------------------------------------------------------

    public function test_customer_profile_response_includes_success_true(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Profile',
            'last_name'  => 'User',
        ]);
        $token = $customer->createToken('api')->plainTextToken;

        $response = $this->getJson('/api/customers/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // -----------------------------------------------------------------------
    // Error responses
    // -----------------------------------------------------------------------

    public function test_unauthenticated_endpoint_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_response_structure_is_consistent_across_success_and_error(): void
    {
        // Success case: register
        $successResponse = $this->postJson('/api/auth/register', [
            'name'                  => 'Struct User',
            'email'                 => 'struct@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $successData = $successResponse->json();
        $this->assertArrayHasKey('success', $successData);
        $this->assertArrayHasKey('data', $successData);
        $this->assertArrayHasKey('message', $successData);
        $this->assertTrue($successData['success']);

        // Error case: login with bad credentials
        $errorResponse = $this->postJson('/api/auth/login', [
            'email'    => 'struct@example.com',
            'password' => 'wrongpassword',
        ]);

        $errorData = $errorResponse->json();
        $this->assertArrayHasKey('success', $errorData);
        $this->assertArrayHasKey('error', $errorData);
        $this->assertArrayHasKey('message', $errorData);
        $this->assertFalse($errorData['success']);
    }
}
