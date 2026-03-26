<?php

namespace Tests\Feature\Api\Review;

use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use App\Filament\Resources\ReviewResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name'        => ['en' => 'Test Product'],
            'slug'        => 'test-product-' . uniqid(),
            'description' => ['en' => 'A test product'],
            'is_active'   => true,
        ], $overrides));
    }

    private function createCustomerWithUser(): array
    {
        $user     = User::factory()->create();
        $customer = $user->customer()->create([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => $user->email,
            'phone'      => '1234567890',
        ]);

        return [$user, $customer];
    }

    // ── Status String Type Tests ──────────────────────────────────────────────

    public function test_review_status_is_stored_as_lowercase_string(): void
    {
        $product = $this->createProduct();
        [, $customer] = $this->createCustomerWithUser();

        $review = Review::create([
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 4,
            'status'      => 'pending',
        ]);

        $this->assertIsString($review->status);
        $this->assertSame('pending', $review->status);
    }

    public function test_new_review_defaults_to_pending_string(): void
    {
        $product = $this->createProduct();
        [, $customer] = $this->createCustomerWithUser();

        $review = Review::create([
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 3,
        ]);

        $fresh = $review->fresh();

        $this->assertIsString($fresh->status);
        $this->assertSame('pending', $fresh->status);
    }

    public function test_approve_action_sets_status_to_approved_string(): void
    {
        $product = $this->createProduct();
        [, $customer] = $this->createCustomerWithUser();

        $review = Review::create([
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 5,
            'status'      => 'pending',
        ]);

        $review->update(['status' => 'approved']);

        $this->assertIsString($review->fresh()->status);
        $this->assertSame('approved', $review->fresh()->status);
    }

    public function test_reject_action_sets_status_to_rejected_string(): void
    {
        $product = $this->createProduct();
        [, $customer] = $this->createCustomerWithUser();

        $review = Review::create([
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 2,
            'status'      => 'pending',
        ]);

        $review->update(['status' => 'rejected']);

        $this->assertIsString($review->fresh()->status);
        $this->assertSame('rejected', $review->fresh()->status);
    }

    // ── Consistency Tests ─────────────────────────────────────────────────────

    public function test_status_values_are_consistent_with_filament_filter_options(): void
    {
        // The valid status values used in ReviewResource filter options:
        //   'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'
        // These MUST match what is actually stored in the database.
        $filamentFilterKeys = ['pending', 'approved', 'rejected'];

        $product = $this->createProduct();

        foreach ($filamentFilterKeys as $status) {
            [, $customer] = $this->createCustomerWithUser();
            $review = Review::create([
                'product_id'  => $product->id,
                'customer_id' => $customer->id,
                'rating'      => 3,
                'status'      => $status,
            ]);

            $this->assertSame(
                $status,
                $review->fresh()->status,
                "Filament filter key '{$status}' must match the exact string stored in the DB"
            );
        }

        // Confirm Filament badge colours align with the same three values
        // by verifying the ReviewResource source uses lowercase keys
        $resourceSource = file_get_contents(
            app_path('Filament/Resources/ReviewResource.php')
        );

        foreach ($filamentFilterKeys as $status) {
            $this->assertStringContainsString(
                "'{$status}'",
                $resourceSource,
                "ReviewResource must reference the lowercase status value '{$status}'"
            );
        }
    }

    public function test_migration_uses_string_column_for_status(): void
    {
        // Verify the column type is a string (varchar), not an enum or integer
        $columnType = Schema::getColumnType('reviews', 'status');

        // MariaDB/MySQL returns 'varchar' or 'string'; SQLite returns 'varchar'
        $this->assertStringContainsStringIgnoringCase(
            'varchar',
            $columnType,
            "Status column should be a string/varchar type, not an enum or integer"
        );
    }

    public function test_migration_default_status_is_pending(): void
    {
        $product = $this->createProduct();
        [, $customer] = $this->createCustomerWithUser();

        // Insert without specifying status - relies on DB default
        \Illuminate\Support\Facades\DB::table('reviews')->insert([
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 4,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $rawStatus = \Illuminate\Support\Facades\DB::table('reviews')
            ->where('product_id', $product->id)
            ->where('customer_id', $customer->id)
            ->value('status');

        $this->assertSame('pending', $rawStatus);
    }

    public function test_review_model_has_no_enum_cast_for_status(): void
    {
        $review = new Review();
        $casts  = $review->getCasts();

        // Status must NOT be cast to any enum; it should remain a plain string
        $this->assertArrayNotHasKey(
            'status',
            $casts,
            "Review model must not cast status field — it should be a plain string"
        );
    }

    public function test_invalid_status_value_is_not_enforced_at_model_level_but_is_inconsistent(): void
    {
        // This test documents that the model itself does NOT validate status values —
        // invalid values can be set (no enum enforcement). This is expected behaviour
        // for a string column without a PHP enum cast.
        $product = $this->createProduct();
        [, $customer] = $this->createCustomerWithUser();

        $review = Review::create([
            'product_id'  => $product->id,
            'customer_id' => $customer->id,
            'rating'      => 3,
            'status'      => 'spam',
        ]);

        // The value is persisted as-is (no validation at model level)
        $this->assertSame('spam', $review->fresh()->status);

        // Confirm 'spam' is NOT one of the three valid application-level values
        $validStatuses = ['pending', 'approved', 'rejected'];
        $this->assertNotContains(
            $review->fresh()->status,
            $validStatuses,
            "Value 'spam' should not be a valid application status — confirms no enum enforcement at DB level"
        );
    }
}
