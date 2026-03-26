<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSchemaVerificationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Table existence
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('phase2TableProvider')]
    public function test_it_creates_all_phase2_tables(string $table): void
    {
        $this->assertTrue(
            Schema::hasTable($table),
            "Table [{$table}] should exist after running migrations"
        );
    }

    public static function phase2TableProvider(): array
    {
        return [
            // Laravel scaffold
            ['users'],
            ['sessions'],
            ['jobs'],
            // Phase 2 core tables
            ['languages'],
            ['currencies'],
            ['customers'],
            ['customer_addresses'],
            ['products'],
            ['product_variants'],
            ['attributes'],
            ['product_attributes'],
            ['product_variant_attributes'],
            ['categories'],
            ['category_product'],
            ['manufacturers'],
            ['product_manufacturer'],
            ['carts'],
            ['cart_items'],
            ['orders'],
            ['order_items'],
            ['order_addresses'],
            ['reviews'],
        ];
    }

    // -------------------------------------------------------------------------
    // Column definitions
    // -------------------------------------------------------------------------

    public function test_languages_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('languages', ['id', 'code', 'name', 'is_default', 'created_at', 'updated_at']));
    }

    public function test_currencies_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('currencies', ['id', 'code', 'name', 'symbol', 'exchange_rate', 'is_base', 'created_at', 'updated_at']));
    }

    public function test_customers_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('customers', ['id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'created_at', 'updated_at']));
    }

    public function test_products_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('products', ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at']));
    }

    public function test_product_variants_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('product_variants', ['id', 'product_id', 'sku', 'regular_price', 'special_price', 'stock_quantity', 'weight', 'is_active']));
    }

    public function test_orders_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('orders', ['id', 'customer_id', 'status', 'total_amount', 'currency_code', 'exchange_rate']));
    }

    public function test_reviews_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('reviews', ['id', 'product_id', 'customer_id', 'rating', 'comment', 'status']));
    }

    // -------------------------------------------------------------------------
    // Unique constraints
    // -------------------------------------------------------------------------

    public function test_languages_code_must_be_unique(): void
    {
        DB::table('languages')->insert(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('languages')->insert(['code' => 'en', 'name' => 'English Duplicate', 'is_default' => false]);
    }

    public function test_currencies_code_must_be_unique(): void
    {
        DB::table('currencies')->insert(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.0, 'is_base' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('currencies')->insert(['code' => 'USD', 'name' => 'US Dollar Dup', 'symbol' => '$', 'exchange_rate' => 1.0, 'is_base' => false]);
    }

    public function test_product_slug_must_be_unique(): void
    {
        DB::table('products')->insert(['name' => 'Product A', 'slug' => 'product-a', 'is_active' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('products')->insert(['name' => 'Product A Dup', 'slug' => 'product-a', 'is_active' => true]);
    }

    public function test_product_variant_sku_must_be_unique(): void
    {
        $productId = DB::table('products')->insertGetId(['name' => 'Widget', 'slug' => 'widget', 'is_active' => true]);

        DB::table('product_variants')->insert([
            'product_id'    => $productId,
            'sku'           => 'SKU-001',
            'regular_price' => 9.99,
            'stock_quantity' => 10,
            'weight'        => 0.5,
            'is_active'     => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('product_variants')->insert([
            'product_id'    => $productId,
            'sku'           => 'SKU-001',
            'regular_price' => 19.99,
            'stock_quantity' => 5,
            'weight'        => 0.5,
            'is_active'     => true,
        ]);
    }

    public function test_category_slug_must_be_unique(): void
    {
        DB::table('categories')->insert(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('categories')->insert(['name' => 'Electronics Dup', 'slug' => 'electronics', 'is_active' => true]);
    }

    public function test_cart_customer_id_must_be_unique(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name'     => 'Alice',
            'email'    => 'alice@example.com',
            'password' => bcrypt('secret'),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'user_id'    => $userId,
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
            'email'      => 'alice@example.com',
        ]);

        DB::table('carts')->insert(['customer_id' => $customerId]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('carts')->insert(['customer_id' => $customerId]);
    }

    public function test_review_product_customer_pair_must_be_unique(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name'     => 'Bob',
            'email'    => 'bob@example.com',
            'password' => bcrypt('secret'),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'user_id'    => $userId,
            'first_name' => 'Bob',
            'last_name'  => 'Jones',
            'email'      => 'bob@example.com',
        ]);

        $productId = DB::table('products')->insertGetId([
            'name'      => 'Review Widget',
            'slug'      => 'review-widget',
            'is_active' => true,
        ]);

        DB::table('reviews')->insert([
            'product_id'  => $productId,
            'customer_id' => $customerId,
            'rating'      => 5,
            'status'      => 'approved',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('reviews')->insert([
            'product_id'  => $productId,
            'customer_id' => $customerId,
            'rating'      => 3,
            'status'      => 'pending',
        ]);
    }

    // -------------------------------------------------------------------------
    // Foreign key constraints
    // -------------------------------------------------------------------------

    public function test_customer_addresses_requires_valid_customer_id(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('customer_addresses')->insert([
            'customer_id'   => 99999,
            'country'       => 'US',
            'city'          => 'New York',
            'address_line_1' => '123 Main St',
            'postcode'      => '10001',
        ]);
    }

    public function test_product_variants_require_valid_product_id(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('product_variants')->insert([
            'product_id'    => 99999,
            'sku'           => 'ORPHAN-001',
            'regular_price' => 9.99,
            'stock_quantity' => 0,
            'weight'        => 0.1,
            'is_active'     => true,
        ]);
    }

    public function test_cart_items_require_valid_cart_and_product(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('cart_items')->insert([
            'cart_id'            => 99999,
            'product_id'         => 99999,
            'product_variant_id' => 99999,
            'quantity'           => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // Self-referential category parent_id allows NULL
    // -------------------------------------------------------------------------

    public function test_category_parent_id_is_nullable(): void
    {
        DB::table('categories')->insert([
            'parent_id' => null,
            'name'      => 'Root Category',
            'slug'      => 'root-category',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'root-category', 'parent_id' => null]);
    }

    public function test_category_supports_self_referential_parent(): void
    {
        $parentId = DB::table('categories')->insertGetId([
            'parent_id' => null,
            'name'      => 'Parent Category',
            'slug'      => 'parent-cat',
            'is_active' => true,
        ]);

        DB::table('categories')->insert([
            'parent_id' => $parentId,
            'name'      => 'Child Category',
            'slug'      => 'child-cat',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'child-cat', 'parent_id' => $parentId]);
    }

    // -------------------------------------------------------------------------
    // down() methods exist for rollback verification
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('downMethodMigrationProvider')]
    public function test_migration_file_has_down_method(string $pattern): void
    {
        $migrations = File::glob(database_path("migrations/{$pattern}"));

        $this->assertNotEmpty($migrations, "Migration matching [{$pattern}] should exist");

        $content = File::get($migrations[0]);
        $this->assertStringContainsString('function down(', $content, "Migration [{$pattern}] should have a down() method");
    }

    public static function downMethodMigrationProvider(): array
    {
        return [
            ['*create_languages_table.php'],
            ['*create_currencies_table.php'],
            ['*create_categories_table.php'],
            ['*create_customers_table.php'],
            ['*create_customer_addresses_table.php'],
            ['*create_manufacturers_table.php'],
            ['*create_products_table.php'],
            ['*create_product_variants_table.php'],
            ['*create_attributes_table.php'],
            ['*create_product_attributes_table.php'],
            ['*create_product_variant_attributes_table.php'],
            ['*create_product_manufacturer_table.php'],
            ['*create_category_product_table.php'],
            ['*create_carts_table.php'],
            ['*create_cart_items_table.php'],
            ['*create_orders_table.php'],
            ['*create_order_items_table.php'],
            ['*create_order_addresses_table.php'],
            ['*create_reviews_table.php'],
        ];
    }
}
