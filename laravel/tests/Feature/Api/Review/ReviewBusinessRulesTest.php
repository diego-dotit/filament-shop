<?php

namespace Tests\Feature\Api\Review;

use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createUserWithCustomer(array $customerData = []): array
    {
        $user     = User::factory()->create();
        $customer = $user->customer()->create(array_merge([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => $user->email,
            'phone'      => '1234567890',
        ], $customerData));

        return [$user, $customer];
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

    // ── Business Rule Tests ───────────────────────────────────────────────────

    public function test_duplicate_review_rejection(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product = $this->createProduct();

        // First review succeeds
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating'  => 5,
                'comment' => 'Great product!',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
        ]);

        // Duplicate review for same customer + product returns 422
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating' => 3,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);

        // Only one review exists in DB
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_pending_review_not_visible_in_public_listing(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product = $this->createProduct();

        // Create a pending review directly
        $review = $product->reviews()->create([
            'customer_id' => $customer->id,
            'rating'      => 4,
            'comment'     => 'Pending comment',
            'status'      => 'pending',
        ]);

        // Pending review is NOT visible in public listing
        $this->getJson("/api/products/{$product->id}/reviews")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Approve the review
        $review->update(['status' => 'approved']);

        // Approved review IS now visible in public listing
        $this->getJson("/api/products/{$product->id}/reviews")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 4);
    }

    public function test_approve_action_transitions_status_and_updates_public_visibility(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product = $this->createProduct();

        $review = $product->reviews()->create([
            'customer_id' => $customer->id,
            'rating'      => 5,
            'comment'     => 'Needs approval',
            'status'      => 'pending',
        ]);

        $admin = User::factory()->create();
        $this->actingAs($admin);

        // Before approve: not visible in public listing
        $this->getJson("/api/products/{$product->id}/reviews")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Use the Filament approve action
        Livewire::test(ListReviews::class)
            ->callTableAction('approve', $review);

        // Verify status changed to approved in DB
        $this->assertDatabaseHas('reviews', [
            'id'     => $review->id,
            'status' => 'approved',
        ]);

        $this->assertSame('approved', $review->fresh()->status);

        // After approve: review is now visible in public listing
        $this->getJson("/api/products/{$product->id}/reviews")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }

    public function test_reject_action_transitions_status_and_updates_public_visibility(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();
        $product = $this->createProduct();

        // Start with an approved review so it's visible
        $review = $product->reviews()->create([
            'customer_id' => $customer->id,
            'rating'      => 3,
            'comment'     => 'Will be rejected',
            'status'      => 'pending',
        ]);

        $admin = User::factory()->create();
        $this->actingAs($admin);

        // Use the Filament reject action
        Livewire::test(ListReviews::class)
            ->callTableAction('reject', $review);

        // Verify status changed to rejected in DB
        $this->assertDatabaseHas('reviews', [
            'id'     => $review->id,
            'status' => 'rejected',
        ]);

        $this->assertSame('rejected', $review->fresh()->status);

        // Rejected review is NOT visible in public listing
        $this->getJson("/api/products/{$product->id}/reviews")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_approved_reviews_visible_in_public_listing(): void
    {
        $product = $this->createProduct();

        [, $customer1] = $this->createUserWithCustomer(['email' => 'buyer1@example.com']);
        [, $customer2] = $this->createUserWithCustomer(['email' => 'buyer2@example.com']);
        [, $customer3] = $this->createUserWithCustomer(['email' => 'buyer3@example.com']);

        // Create multiple approved reviews
        $product->reviews()->create(['customer_id' => $customer1->id, 'rating' => 5, 'status' => 'approved']);
        $product->reviews()->create(['customer_id' => $customer2->id, 'rating' => 4, 'status' => 'approved']);

        // Also create a pending review — should NOT appear
        $product->reviews()->create(['customer_id' => $customer3->id, 'rating' => 2, 'status' => 'pending']);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Verify ratings from approved reviews are present
        $ratings = collect($response->json('data'))->pluck('rating')->sort()->values()->toArray();
        $this->assertSame([4, 5], $ratings);
    }

    public function test_public_listing_excludes_pending_and_rejected_reviews(): void
    {
        $product = $this->createProduct();

        [, $customer1] = $this->createUserWithCustomer(['email' => 'mix1@example.com']);
        [, $customer2] = $this->createUserWithCustomer(['email' => 'mix2@example.com']);
        [, $customer3] = $this->createUserWithCustomer(['email' => 'mix3@example.com']);

        $product->reviews()->create(['customer_id' => $customer1->id, 'rating' => 5, 'status' => 'approved']);
        $product->reviews()->create(['customer_id' => $customer2->id, 'rating' => 3, 'status' => 'pending']);
        $product->reviews()->create(['customer_id' => $customer3->id, 'rating' => 1, 'status' => 'rejected']);

        $response = $this->getJson("/api/products/{$product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }

    public function test_multiple_customers_can_review_same_product(): void
    {
        $product = $this->createProduct();

        [$user1, $customer1] = $this->createUserWithCustomer(['email' => 'multi1@example.com']);
        [$user2, $customer2] = $this->createUserWithCustomer(['email' => 'multi2@example.com']);

        // First customer submits a review
        $this->actingAs($user1, 'sanctum')
            ->postJson("/api/products/{$product->id}/reviews", ['rating' => 5])
            ->assertStatus(201);

        // Second customer submits a review for the same product
        $this->actingAs($user2, 'sanctum')
            ->postJson("/api/products/{$product->id}/reviews", ['rating' => 4])
            ->assertStatus(201);

        // Both reviews exist in DB
        $this->assertDatabaseHas('reviews', [
            'product_id'  => $product->id,
            'customer_id' => $customer1->id,
        ]);
        $this->assertDatabaseHas('reviews', [
            'product_id'  => $product->id,
            'customer_id' => $customer2->id,
        ]);

        $this->assertDatabaseCount('reviews', 2);

        // Approve both and verify both visible in public listing
        Review::where('product_id', $product->id)->update(['status' => 'approved']);

        $this->getJson("/api/products/{$product->id}/reviews")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_same_customer_can_review_different_products(): void
    {
        [$user, $customer] = $this->createUserWithCustomer();

        $productA = $this->createProduct(['slug' => 'product-a-' . uniqid()]);
        $productB = $this->createProduct(['slug' => 'product-b-' . uniqid()]);

        // Customer reviews product A
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$productA->id}/reviews", [
                'rating'  => 5,
                'comment' => 'Love product A!',
            ])
            ->assertStatus(201);

        // Same customer reviews product B (different product — this must succeed)
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$productB->id}/reviews", [
                'rating'  => 4,
                'comment' => 'Product B is good too!',
            ])
            ->assertStatus(201);

        // Two separate reviews exist in DB
        $this->assertDatabaseHas('reviews', [
            'product_id'  => $productA->id,
            'customer_id' => $customer->id,
            'rating'      => 5,
        ]);
        $this->assertDatabaseHas('reviews', [
            'product_id'  => $productB->id,
            'customer_id' => $customer->id,
            'rating'      => 4,
        ]);

        $this->assertDatabaseCount('reviews', 2);
    }
}
