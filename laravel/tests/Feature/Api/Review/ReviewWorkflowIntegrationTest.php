<?php

namespace Tests\Feature\Api\Review;

use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewWorkflowIntegrationTest extends TestCase
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

    // ── Integration test ─────────────────────────────────────────────────────

    public function test_complete_review_workflow_from_submission_to_public_visibility(): void
    {
        // ── Step 1: Setup ─────────────────────────────────────────────────────
        [$user, $customer] = $this->createUserWithCustomer(['email' => 'customer@example.com']);
        $product           = $this->createProduct();
        $admin             = User::factory()->create();

        // ── Step 2: Authenticated customer submits review ─────────────────────
        $submitResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/products/{$product->id}/reviews", [
                'rating'  => 5,
                'comment' => 'Absolutely love this product!',
            ]);

        $submitResponse->assertStatus(201)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Absolutely love this product!')
            ->assertJsonPath('data.status', 'pending');

        // ── Step 3: Verify review is in DB with status=pending ────────────────
        $this->assertDatabaseHas('reviews', [
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 5,
            'status'      => 'pending',
        ]);

        $review = Review::where('product_id', $product->id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // ── Step 4: Verify review NOT visible in public listing ───────────────
        $publicListBefore = $this->getJson("/api/products/{$product->id}/reviews");

        $publicListBefore->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // ── Step 5: Admin approves the review via Filament action ─────────────
        $this->actingAs($admin);

        Livewire::test(ListReviews::class)
            ->callTableAction('approve', $review);

        // ── Step 6: Verify review status changed to approved in DB ────────────
        $this->assertDatabaseHas('reviews', [
            'id'     => $review->id,
            'status' => 'approved',
        ]);

        $this->assertSame('approved', $review->fresh()->status);

        // ── Step 7: Verify review NOW visible in public listing ───────────────
        $publicListAfter = $this->getJson("/api/products/{$product->id}/reviews");

        $publicListAfter->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.0.comment', 'Absolutely love this product!');

        // ── Step 8: Another customer can view approved review without auth ─────
        [$secondUser] = $this->createUserWithCustomer(['email' => 'viewer@example.com']);

        $secondUserResponse = $this->getJson("/api/products/{$product->id}/reviews");

        $secondUserResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }
}
