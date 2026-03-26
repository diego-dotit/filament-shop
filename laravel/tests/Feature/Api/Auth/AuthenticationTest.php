<?php

namespace Tests\Feature\Api\Auth;

use App\Domains\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // POST /auth/register
    // -----------------------------------------------------------------------

    public function test_successful_registration_creates_user_and_customer(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('customers', ['email' => 'jane@example.com', 'first_name' => 'Jane']);
    }

    public function test_register_validates_name_is_required(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_register_validates_email_uniqueness(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'existing@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validates_password_minimum_length(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_validates_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'doesnotmatch',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // -----------------------------------------------------------------------
    // POST /auth/login
    // -----------------------------------------------------------------------

    public function test_successful_login_returns_token_and_user_profile(): void
    {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->customer()->create([
            'first_name' => 'John',
            'last_name'  => 'Smith',
            'email'      => 'user@example.com',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Invalid credentials.']);
    }

    // -----------------------------------------------------------------------
    // GET /auth/me
    // -----------------------------------------------------------------------

    public function test_me_endpoint_returns_authenticated_user_with_customer_details(): void
    {
        $user = User::factory()->create();
        $user->customer()->create([
            'first_name' => 'Alice',
            'last_name'  => 'Wonder',
            'email'      => $user->email,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ])
            ->assertJsonPath('data.first_name', 'Alice');
    }

    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_token_based_authentication_flow(): void
    {
        // Register
        $registerResponse = $this->postJson('/api/auth/register', [
            'name'                  => 'Token User',
            'email'                 => 'tokenuser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $registerResponse->assertStatus(201);

        // Login to get token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => 'tokenuser@example.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token');

        // Access /me with token
        $meResponse = $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ]);
        $meResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'tokenuser@example.com');
    }
}
