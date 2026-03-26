<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

/**
 * Structural suite test verifying the complete seeder set.
 *
 * All assertions are file/source-level — no database connection required.
 * Covers: DatabaseSeeder, LanguageSeeder, CurrencySeeder, CategorySeeder,
 *         ManufacturerSeeder, ProductSeeder, CustomerSeeder.
 */
class SeederSuiteTest extends TestCase
{
    private string $seedersDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedersDir = __DIR__ . '/../../../database/seeders';
    }

    // -------------------------------------------------------------------------
    // All seeder files exist
    // -------------------------------------------------------------------------

    public function test_all_seeder_files_exist(): void
    {
        $expectedFiles = [
            'DatabaseSeeder.php',
            'LanguageSeeder.php',
            'CurrencySeeder.php',
            'CategorySeeder.php',
            'ManufacturerSeeder.php',
            'ProductSeeder.php',
            'CustomerSeeder.php',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists(
                "{$this->seedersDir}/{$file}",
                "Expected seeder file {$file} to exist"
            );
        }
    }

    // -------------------------------------------------------------------------
    // DatabaseSeeder calls every seeder in the suite
    // -------------------------------------------------------------------------

    public function test_database_seeder_calls_all_required_seeders(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/DatabaseSeeder.php");

        $requiredSeeders = [
            'LanguageSeeder::class',
            'CurrencySeeder::class',
            'CategorySeeder::class',
            'ManufacturerSeeder::class',
            'ProductSeeder::class',
            'CustomerSeeder::class',
        ];

        foreach ($requiredSeeders as $seeder) {
            $this->assertStringContainsString(
                $seeder,
                $contents,
                "DatabaseSeeder should call {$seeder}"
            );
        }
    }

    public function test_database_seeder_uses_this_call(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/DatabaseSeeder.php");

        $this->assertStringContainsString('$this->call(', $contents);
    }

    // -------------------------------------------------------------------------
    // Every seeder extends Illuminate Seeder with correct namespace
    // -------------------------------------------------------------------------

    public function test_all_seeders_have_correct_namespace_and_extend_seeder(): void
    {
        $seederClasses = [
            'DatabaseSeeder',
            'LanguageSeeder',
            'CurrencySeeder',
            'CategorySeeder',
            'ManufacturerSeeder',
            'ProductSeeder',
            'CustomerSeeder',
        ];

        foreach ($seederClasses as $class) {
            $contents = file_get_contents("{$this->seedersDir}/{$class}.php");

            $this->assertStringContainsString(
                'namespace Database\Seeders;',
                $contents,
                "{$class} must declare namespace Database\\Seeders"
            );
            $this->assertStringContainsString(
                "class {$class}",
                $contents,
                "{$class} must declare its class"
            );
            $this->assertStringContainsString(
                'extends Seeder',
                $contents,
                "{$class} must extend Seeder"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Idempotency: updateOrCreate or firstOrCreate present in every data seeder
    // -------------------------------------------------------------------------

    public function test_language_seeder_is_idempotent(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/LanguageSeeder.php");

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_currency_seeder_is_idempotent(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CurrencySeeder.php");

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_category_seeder_is_idempotent(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CategorySeeder.php");

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_manufacturer_seeder_is_idempotent(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ManufacturerSeeder.php");

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_product_seeder_is_idempotent(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ProductSeeder.php");

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_customer_seeder_is_idempotent(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CustomerSeeder.php");

        // Customer seeder uses updateOrCreate for the User
        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    // -------------------------------------------------------------------------
    // LanguageSeeder: at least 1 language with is_default
    // -------------------------------------------------------------------------

    public function test_language_seeder_declares_default_language(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/LanguageSeeder.php");

        $this->assertStringContainsString('is_default', $contents);
        $this->assertStringContainsString("'en'", $contents);
    }

    // -------------------------------------------------------------------------
    // CurrencySeeder: at least 1 currency with is_base
    // -------------------------------------------------------------------------

    public function test_currency_seeder_declares_base_currency(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CurrencySeeder.php");

        $this->assertStringContainsString('is_base', $contents);
        $this->assertStringContainsString("'USD'", $contents);
    }

    // -------------------------------------------------------------------------
    // CategorySeeder: at least 3 root categories + parent-child structure
    // -------------------------------------------------------------------------

    public function test_category_seeder_declares_at_least_three_root_categories(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CategorySeeder.php");

        $this->assertStringContainsString('Electronics', $contents);
        $this->assertStringContainsString('Clothing', $contents);
        $this->assertStringContainsString('Home', $contents);
    }

    public function test_category_seeder_declares_parent_child_relationship(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CategorySeeder.php");

        $this->assertStringContainsString('parent_id', $contents);
        // Child categories exist (e.g. Mobile Phones under Electronics)
        $this->assertStringContainsString('Mobile', $contents);
        $this->assertStringContainsString('Laptops', $contents);
    }

    // -------------------------------------------------------------------------
    // ManufacturerSeeder: at least 2 manufacturers
    // -------------------------------------------------------------------------

    public function test_manufacturer_seeder_declares_at_least_two_manufacturers(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ManufacturerSeeder.php");

        $this->assertStringContainsString('TechBrand', $contents);
        $this->assertStringContainsString('FashionCo', $contents);
    }

    // -------------------------------------------------------------------------
    // ProductSeeder: at least 5 products, variants, variant attrs, product attrs
    // -------------------------------------------------------------------------

    public function test_product_seeder_declares_at_least_five_product_slugs(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ProductSeeder.php");

        // Count occurrences of 'slug' key assignments inside product definitions
        $slugMatches = preg_match_all("/'slug'\s*=>/", $contents, $m);
        $this->assertGreaterThanOrEqual(5, $slugMatches);
    }

    public function test_product_seeder_declares_variants_with_sku_and_price(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ProductSeeder.php");

        $this->assertStringContainsString("'sku'", $contents);
        $this->assertStringContainsString('regular_price', $contents);
        $this->assertStringContainsString('stock_quantity', $contents);
    }

    public function test_product_seeder_declares_variant_attributes(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ProductSeeder.php");

        $this->assertStringContainsString('ProductVariantAttribute', $contents);
    }

    public function test_product_seeder_declares_product_level_attributes(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ProductSeeder.php");

        $this->assertStringContainsString('ProductAttribute', $contents);
        $this->assertStringContainsString('Attribute', $contents);
    }

    public function test_product_seeder_assigns_categories(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ProductSeeder.php");

        $this->assertStringContainsString('categories()', $contents);
        $this->assertStringContainsString('sync', $contents);
    }

    // -------------------------------------------------------------------------
    // CustomerSeeder: test customer, address, cart
    // -------------------------------------------------------------------------

    public function test_customer_seeder_declares_test_customer_email(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CustomerSeeder.php");

        $this->assertStringContainsString('test@example.com', $contents);
    }

    public function test_customer_seeder_hashes_password(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CustomerSeeder.php");

        $this->assertStringContainsString('Hash::make', $contents);
    }

    public function test_customer_seeder_declares_address_fields(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CustomerSeeder.php");

        $this->assertStringContainsString('addresses()', $contents);
        $this->assertStringContainsString('address_line_1', $contents);
        $this->assertStringContainsString('city', $contents);
        $this->assertStringContainsString('postcode', $contents);
    }

    public function test_customer_seeder_creates_empty_cart(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CustomerSeeder.php");

        $this->assertStringContainsString('cart()', $contents);
        $this->assertStringContainsString('firstOrCreate', $contents);
    }

    // -------------------------------------------------------------------------
    // Structural: each seeder imports correct domain models
    // -------------------------------------------------------------------------

    public function test_language_seeder_imports_language_model(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/LanguageSeeder.php");

        $this->assertStringContainsString('use App\Domains\Language\Models\Language;', $contents);
    }

    public function test_currency_seeder_imports_currency_model(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CurrencySeeder.php");

        $this->assertStringContainsString('use App\Domains\Currency\Models\Currency;', $contents);
    }

    public function test_category_seeder_imports_category_model(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CategorySeeder.php");

        $this->assertStringContainsString('use App\Domains\Category\Models\Category;', $contents);
    }

    public function test_manufacturer_seeder_imports_manufacturer_model(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ManufacturerSeeder.php");

        $this->assertStringContainsString('use App\Domains\Manufacturer\Models\Manufacturer;', $contents);
    }

    public function test_product_seeder_imports_product_models(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/ProductSeeder.php");

        $this->assertStringContainsString('use App\Domains\Product\Models\Product;', $contents);
        $this->assertStringContainsString('use App\Domains\Product\Models\ProductVariant;', $contents);
    }

    public function test_customer_seeder_imports_user_and_customer_models(): void
    {
        $contents = file_get_contents("{$this->seedersDir}/CustomerSeeder.php");

        $this->assertStringContainsString('use App\Models\User;', $contents);
        $this->assertStringContainsString('use App\Domains\Customer\Models\Customer;', $contents);
    }
}
