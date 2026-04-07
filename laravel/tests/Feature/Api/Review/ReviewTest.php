<?php

namespace Tests\Feature\Api\Review;

use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createUserWithCustomer(array $customerData = []): array
    {
        $customer = Customer::factory()->create(array_merge([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'phone'      => '1234567890',
        ], $customerData));

        return [$customer, $customer];
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name'        => ['en' => 'Test Product'],
            'slug'        => 'test-product-' . uniqid(),
            'description' => ['en' => 'A test product'],
            'is_active'   => true,
        ], $overrides));
    }

    // ── POST /api/products/{productId}/reviews ───────────────────────────────

    public function test_submit_review_requires_authentication(): void
    {
        $product = $this->createProduct();

        $response = $this->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 5,
        ]);

        $response->assertStatus(401);
    }

    public function test_submit_review_creates_review_with_pending_status(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product           = $this->createProduct();

        $response = $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating'  => 4,
                'comment' => 'Great product!',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'rating', 'comment', 'customer_name', 'status', 'created_at'],
            ])
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', 'Great product!')
            ->assertJsonPath('data.customer_name', 'Jane Doe')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('reviews', [
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 4,
            'status'      => 'pending',
        ]);
    }

    public function test_submit_review_ignores_status_parameter_from_request(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product           = $this->createProduct();

        // Even when 'status' => 'approved' is sent in request, it must be ignored
        $response = $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating' => 5,
                'status' => 'approved',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'status'     => 'pending',
        ]);
    }

    public function test_pending_review_not_visible_in_public_listing_until_approved(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product           = $this->createProduct();

        // Submit a review — it starts as pending
        $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating'  => 5,
                'comment' => 'Wonderful!',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        // Pending review is NOT visible in the public listing
        $listResponse = $this->getJson("/api/products/{$product->id}/reviews");
        $listResponse->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Simulate moderation: approve the review
        Review::where('product_id', $product->id)
            ->where('customer_id', $customer->id)
            ->update(['status' => 'approved']);

        // Now the approved review IS visible in the public listing
        $approvedResponse = $this->getJson("/api/products/{$product->id}/reviews");
        $approvedResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }

    public function test_submit_review_comment_is_optional(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product           = $this->createProduct();

        $response = $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.comment', null);
    }

    public function test_submit_review_validates_rating_is_required(): void
    {
        [$user] = $this->createUserWithCustomer();
        $product = $this->createProduct();

        $response = $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_submit_review_validates_rating_minimum_is_1(): void
    {
        [$user] = $this->createUserWithCustomer();
        $product = $this->createProduct();

        $response = $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_submit_review_validates_rating_maximum_is_5(): void
    {
        [$user] = $this->createUserWithCustomer();
        $product = $this->createProduct();

        $response = $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating' => 6,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_submit_review_rejects_duplicate_review_for_same_product(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product           = $this->createProduct();

        // Create first review directly
        $product->reviews()->create([
            'customer_id' => $customer->id,
            'rating'      => 5,
            'status'      => 'pending',
        ]);

        // Attempt second review for the same product
        $response = $this->actingAs($user, 'customers')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating' => 3,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_submit_review_returns_404_for_invalid_product_id(): void
    {
        [$user] = $this->createUserWithCustomer();

        $response = $this->actingAs($user, 'customers')
            ->postJson('/api/products/99999/reviews', [
                'rating' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    // ── GET /api/products/{productId}/reviews ────────────────────────────────

    public function test_list_reviews_is_public_and_returns_approved_reviews_only(): void
    {
        $product = $this->createProduct();

        [, $customer1] = $this->createUserWithCustomer(['email' => 'c1@example.com']);
        [, $customer2] = $this->createUserWithCustomer(['email' => 'c2@example.com']);
        [, $customer3] = $this->createUserWithCustomer(['email' => 'c3@example.com']);

        $product->reviews()->create(['customer_id' => $customer1->id, 'rating' => 5, 'status' => 'approved']);
        $product->reviews()->create(['customer_id' => $customer2->id, 'rating' => 3, 'status' => 'pending']);
        $product->reviews()->create(['customer_id' => $customer3->id, 'rating' => 1, 'status' => 'rejected']);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }

    public function test_list_reviews_returns_paginated_results(): void
    {
        $product = $this->createProduct();

        for ($i = 0; $i < 5; $i++) {
            [, $customer] = $this->createUserWithCustomer(['email' => "customer{$i}@example.com"]);
            $product->reviews()->create([
                'customer_id' => $customer->id,
                'rating'      => 4,
                'status'      => 'approved',
            ]);
        }

        $response = $this->getJson("/api/products/{$product->id}/reviews?per_page=3");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 5);
    }

    public function test_list_reviews_returns_correct_resource_structure(): void
    {
        $product = $this->createProduct();

        [, $customer] = $this->createUserWithCustomer();
        $product->reviews()->create([
            'customer_id' => $customer->id,
            'rating'      => 5,
            'comment'     => 'Excellent!',
            'status'      => 'approved',
        ]);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    ['id', 'rating', 'comment', 'customer_name', 'created_at'],
                ],
            ])
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.0.comment', 'Excellent!')
            ->assertJsonPath('data.0.customer_name', 'Jane Doe');
    }

    public function test_list_reviews_returns_404_for_invalid_product_id(): void
    {
        $response = $this->getJson('/api/products/99999/reviews');

        $response->assertStatus(404);
    }

    public function test_list_reviews_returns_empty_list_when_no_approved_reviews(): void
    {
        $product = $this->createProduct();

        [, $customer] = $this->createUserWithCustomer();
        $product->reviews()->create([
            'customer_id' => $customer->id,
            'rating'      => 4,
            'status'      => 'pending',
        ]);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
