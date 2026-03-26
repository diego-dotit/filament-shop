<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CartMigrationTest extends TestCase
{
    public function test_carts_migration_file_exists(): void
    {
        $migrations = File::glob(database_path('migrations/*create_carts_table.php'));

        $this->assertNotEmpty($migrations, 'create_carts_table migration should exist');
    }

    public function test_cart_items_migration_file_exists(): void
    {
        $migrations = File::glob(database_path('migrations/*create_cart_items_table.php'));

        $this->assertNotEmpty($migrations, 'create_cart_items_table migration should exist');
    }

    public function test_carts_migration_defines_required_columns(): void
    {
        $migrations = File::glob(database_path('migrations/*create_carts_table.php'));
        $this->assertNotEmpty($migrations);

        $content = File::get($migrations[0]);

        $this->assertStringContainsString('$table->id()', $content);
        $this->assertStringContainsString("'customer_id'", $content);
        $this->assertStringContainsString('timestamps()', $content);
        $this->assertStringContainsString("unique()", $content);
        $this->assertStringContainsString("constrained('customers')", $content);
    }

    public function test_cart_items_migration_defines_required_columns(): void
    {
        $migrations = File::glob(database_path('migrations/*create_cart_items_table.php'));
        $this->assertNotEmpty($migrations);

        $content = File::get($migrations[0]);

        $this->assertStringContainsString("'cart_id'", $content);
        $this->assertStringContainsString("'product_id'", $content);
        $this->assertStringContainsString("'product_variant_id'", $content);
        $this->assertStringContainsString("'quantity'", $content);
        $this->assertStringContainsString('timestamps()', $content);
        $this->assertStringContainsString("constrained('carts')", $content);
        $this->assertStringContainsString("constrained('products')", $content);
        $this->assertStringContainsString("constrained('product_variants')", $content);
    }

    public function test_carts_migration_timestamp_is_after_customers_migration(): void
    {
        $cartsMigrations = File::glob(database_path('migrations/*create_carts_table.php'));
        $customersMigrations = File::glob(database_path('migrations/*create_customers_table.php'));

        $this->assertNotEmpty($cartsMigrations);
        $this->assertNotEmpty($customersMigrations);

        $cartsTimestamp = basename($cartsMigrations[0]);
        $customersTimestamp = basename($customersMigrations[0]);

        $this->assertGreaterThan($customersTimestamp, $cartsTimestamp,
            'carts migration should run after customers migration');
    }

    public function test_cart_items_migration_timestamp_is_after_carts_migration(): void
    {
        $cartItemsMigrations = File::glob(database_path('migrations/*create_cart_items_table.php'));
        $cartsMigrations = File::glob(database_path('migrations/*create_carts_table.php'));

        $this->assertNotEmpty($cartItemsMigrations);
        $this->assertNotEmpty($cartsMigrations);

        $cartItemsTimestamp = basename($cartItemsMigrations[0]);
        $cartsTimestamp = basename($cartsMigrations[0]);

        $this->assertGreaterThan($cartsTimestamp, $cartItemsTimestamp,
            'cart_items migration should run after carts migration');
    }
}
