<?php

namespace Tests\Feature;

use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies that the order_items.product_variant_id FK uses ON DELETE SET NULL,
 * so that deleting a ProductVariant does not raise a constraint error and
 * instead sets the FK column to NULL on any referencing order_items rows.
 */
class OrderItemProductVariantFkTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Happy path – deleting a variant nullifies the FK column
    // -------------------------------------------------------------------------

    public function test_deleting_product_variant_sets_order_item_product_variant_id_to_null(): void
    {
        // Arrange: create an order item that references a specific variant
        $variant   = ProductVariant::factory()->create();
        $orderItem = OrderItem::factory()->create(['product_variant_id' => $variant->id]);

        // Pre-condition: the FK column points at the variant
        $this->assertDatabaseHas('order_items', [
            'id'                 => $orderItem->id,
            'product_variant_id' => $variant->id,
        ]);

        // Act: delete the variant – must NOT throw a FK constraint exception
        $variant->delete();

        // Assert: the order item's FK column is now NULL
        $this->assertDatabaseHas('order_items', [
            'id'                 => $orderItem->id,
            'product_variant_id' => null,
        ]);
    }

    public function test_deleting_variant_without_order_items_succeeds(): void
    {
        $variant = ProductVariant::factory()->create();

        // No order items reference this variant
        $variant->delete();

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_product_variant_id_column_is_nullable_after_migration(): void
    {
        // Insert an order_item row with product_variant_id = NULL directly via DB
        $orderItem = OrderItem::factory()->create();

        DB::table('order_items')
            ->where('id', $orderItem->id)
            ->update(['product_variant_id' => null]);

        $this->assertDatabaseHas('order_items', [
            'id'                 => $orderItem->id,
            'product_variant_id' => null,
        ]);
    }

    public function test_order_items_with_existing_variant_are_unaffected(): void
    {
        $variant   = ProductVariant::factory()->create();
        $item1     = OrderItem::factory()->create(['product_variant_id' => $variant->id]);
        $otherItem = OrderItem::factory()->create(); // references a different variant

        // Only delete the specific variant
        $variant->delete();

        // item1 should now have NULL
        $this->assertDatabaseHas('order_items', ['id' => $item1->id, 'product_variant_id' => null]);

        // otherItem's variant is still intact, so it keeps its FK value
        $this->assertNotNull(
            DB::table('order_items')->where('id', $otherItem->id)->value('product_variant_id')
        );
    }
}
