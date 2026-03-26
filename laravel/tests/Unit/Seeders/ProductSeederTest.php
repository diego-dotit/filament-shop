<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

/**
 * Structural tests for ProductSeeder.
 *
 * These tests validate class structure and idempotency patterns
 * without requiring a database connection.
 */
class ProductSeederTest extends TestCase
{
    private string $seederPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seederPath = __DIR__ . '/../../../database/seeders/ProductSeeder.php';
    }

    public function test_product_seeder_file_exists(): void
    {
        $this->assertFileExists($this->seederPath);
    }

    public function test_product_seeder_correct_namespace_and_class(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('namespace Database\Seeders;', $contents);
        $this->assertStringContainsString('class ProductSeeder', $contents);
        $this->assertStringContainsString('extends Seeder', $contents);
    }

    public function test_product_seeder_uses_update_or_create_for_idempotency(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_product_seeder_contains_at_least_five_products(): void
    {
        $contents = file_get_contents($this->seederPath);

        // Each product has a unique slug; check for at least 5 distinct slug references
        $slugCount = substr_count($contents, "'slug'");
        $this->assertGreaterThanOrEqual(5, $slugCount);
    }

    public function test_product_seeder_sets_is_active_true(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('is_active', $contents);
        $this->assertStringContainsString('true', $contents);
    }

    public function test_product_seeder_creates_variants_with_sku(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('variants()', $contents);
        $this->assertStringContainsString("'sku'", $contents);
        $this->assertStringContainsString('regular_price', $contents);
        $this->assertStringContainsString('stock_quantity', $contents);
    }

    public function test_product_seeder_creates_variant_attributes(): void
    {
        $contents = file_get_contents($this->seederPath);

        // ProductVariantAttribute columns are name and value
        $this->assertStringContainsString('ProductVariantAttribute', $contents);
    }

    public function test_product_seeder_creates_product_level_attributes(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('Attribute', $contents);
        $this->assertStringContainsString('ProductAttribute', $contents);
    }

    public function test_product_seeder_assigns_categories(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('categories()', $contents);
        $this->assertStringContainsString('Category', $contents);
    }

    public function test_product_seeder_imports_correct_models(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('use App\Domains\Product\Models\Product;', $contents);
        $this->assertStringContainsString('use App\Domains\Product\Models\ProductVariant;', $contents);
        $this->assertStringContainsString('use App\Domains\Category\Models\Category;', $contents);
    }

    public function test_database_seeder_calls_product_seeder(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/DatabaseSeeder.php'
        );

        $this->assertStringContainsString('ProductSeeder', $contents);
    }
}
