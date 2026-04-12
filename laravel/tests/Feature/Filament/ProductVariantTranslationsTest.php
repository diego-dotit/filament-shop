<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Filament\Resources\Product\Pages\CreateProduct;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Smoke tests for T6.4: create → view → edit round-trip for product variants
 * with translatable names.
 *
 * Verifies that:
 * - Creating a product with multi-language variant names packs them into JSON
 * - The `product_variants.name` column stores valid JSON keyed by locale
 * - The edit form unpacks stored JSON back into per-locale name_{code} fields
 * - Default language name is required; non-default language names are optional
 * - Full round-trip is consistent (create → edit reload → same names displayed)
 */
class ProductVariantTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Create: variant names stored as JSON ─────────────────────────────

    /**
     * After creating a product with two variants, each with EN + FR names,
     * the product_variants rows must have a valid JSON name column.
     */
    public function test_create_product_with_variant_names_stores_json_in_database(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => 'Coloured T-Shirt',
                'name_fr' => 'T-Shirt Coloré',
                'slug' => 'coloured-t-shirt',
                'is_active' => true,
                'variants' => [
                    [
                        'name_en' => 'Blue T-Shirt',
                        'name_fr' => 'T-Shirt Bleu',
                        'sku' => 'SKU-BLUE-001',
                        'regular_price' => 29.99,
                        'stock_quantity' => 10,
                        'weight' => 0.3,
                        'is_active' => true,
                    ],
                    [
                        'name_en' => 'Red T-Shirt',
                        'name_fr' => 'T-Shirt Rouge',
                        'sku' => 'SKU-RED-001',
                        'regular_price' => 29.99,
                        'stock_quantity' => 5,
                        'weight' => 0.3,
                        'is_active' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'coloured-t-shirt')->first();
        $this->assertNotNull($product, 'Product should be saved');

        // Variant 1: Blue T-Shirt
        $blue = ProductVariant::where(['product_id' => $product->id, 'sku' => 'SKU-BLUE-001'])->first();
        $this->assertNotNull($blue, 'Blue variant should exist in DB');
        $this->assertEquals('Blue T-Shirt', $blue->getTranslation('name', 'en'));
        $this->assertEquals('T-Shirt Bleu', $blue->getTranslation('name', 'fr'));

        // Variant 2: Red T-Shirt
        $red = ProductVariant::where(['product_id' => $product->id, 'sku' => 'SKU-RED-001'])->first();
        $this->assertNotNull($red, 'Red variant should exist in DB');
        $this->assertEquals('Red T-Shirt', $red->getTranslation('name', 'en'));
        $this->assertEquals('T-Shirt Rouge', $red->getTranslation('name', 'fr'));
    }

    /**
     * The name column in product_variants must be valid JSON containing locale keys.
     */
    public function test_variant_name_column_contains_valid_json_with_locale_keys(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => 'Widget',
                'slug' => 'widget',
                'is_active' => true,
                'variants' => [
                    [
                        'name_en' => 'Small Widget',
                        'name_fr' => 'Petit Widget',
                        'sku' => 'SKU-WIDGET-S',
                        'regular_price' => 9.99,
                        'stock_quantity' => 20,
                        'weight' => 0.1,
                        'is_active' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'widget')->first();
        $variant = ProductVariant::where('sku', 'SKU-WIDGET-S')->first();

        $this->assertNotNull($variant);

        // The raw name column must be a JSON string with locale keys
        $rawName = $variant->getRawOriginal('name');
        $decoded = json_decode($rawName, true);

        $this->assertIsArray($decoded, 'name column must be valid JSON');
        $this->assertArrayHasKey('en', $decoded, 'JSON must contain en key');
        $this->assertArrayHasKey('fr', $decoded, 'JSON must contain fr key');
        $this->assertSame('Small Widget', $decoded['en']);
        $this->assertSame('Petit Widget', $decoded['fr']);
    }

    // ── Validation: default language name required, non-default optional ──

    /**
     * The default-language name field inside the variant tab must be required.
     */
    public function test_variant_default_language_name_is_required(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => 'Some Product',
                'slug' => 'some-product',
                'is_active' => true,
                'variants' => [
                    [
                        'name_en' => '', // missing default language name
                        'name_fr' => 'Variante Française',
                        'sku' => 'SKU-VAL-001',
                        'regular_price' => 10.00,
                        'stock_quantity' => 1,
                        'weight' => 0.5,
                        'is_active' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['variants.0.name_en']);
    }

    /**
     * Non-default language names in a variant tab must be optional.
     * Form should submit without errors when the non-default name is blank.
     */
    public function test_variant_non_default_language_name_is_optional(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => 'English-Only Product',
                'slug' => 'english-only-product',
                'is_active' => true,
                'variants' => [
                    [
                        'name_en' => 'English-Only Variant',
                        'name_fr' => '', // non-default language name omitted
                        'sku' => 'SKU-EN-ONLY',
                        'regular_price' => 15.00,
                        'stock_quantity' => 3,
                        'weight' => 0.2,
                        'is_active' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors(['variants.0.name_fr']);

        $variant = ProductVariant::where('sku', 'SKU-EN-ONLY')->first();
        $this->assertNotNull($variant);
        $this->assertEquals('English-Only Variant', $variant->getTranslation('name', 'en'));
        // Non-default name should not be stored (empty string excluded)
        $this->assertSame('', $variant->getTranslation('name', 'fr', false));
    }

    // ── Edit: variant names unpacked from JSON into per-locale fields ─────

    /**
     * When editing a product, the edit form must unpack each variant's JSON
     * name column back into per-locale name_{code} fields so the tab inputs
     * display the correct values.
     */
    public function test_edit_form_shows_unpacked_variant_names_in_tabs(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        $product = Product::factory()->create([
            'name' => ['en' => 'Test Product', 'fr' => 'Produit Test'],
            'slug' => 'test-product-edit',
            'is_active' => true,
        ]);

        // Create variant with pre-packed JSON name
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => ['en' => 'Blue T-Shirt', 'fr' => 'T-Shirt Bleu'],
            'sku' => 'SKU-EDIT-BLUE',
            'regular_price' => 29.99,
            'is_active' => true,
        ]);

        // The edit form should render successfully and show variant names
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(function (array $state) {
                // Find the variant item in the repeater state
                $variantItems = collect($state['variants'] ?? []);

                $this->assertNotEmpty($variantItems, 'Variants should be loaded in edit form');

                // Find our specific variant by SKU
                $found = $variantItems->first(fn ($item) => ($item['sku'] ?? null) === 'SKU-EDIT-BLUE');
                $this->assertNotNull($found, 'Variant SKU-EDIT-BLUE should appear in form');

                $this->assertSame('Blue T-Shirt', $found['name_en'] ?? null,
                    'EN name should be unpacked into name_en field');
                $this->assertSame('T-Shirt Bleu', $found['name_fr'] ?? null,
                    'FR name should be unpacked into name_fr field');
            });
    }

    // ── Full round-trip ───────────────────────────────────────────────────

    /**
     * Full round-trip: create product with variant names → reload edit page →
     * names still display correctly.
     */
    public function test_full_round_trip_create_then_edit_shows_consistent_variant_names(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        // ── Step 1: Create
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => 'Round-Trip Product',
                'slug' => 'round-trip-product',
                'is_active' => true,
                'variants' => [
                    [
                        'name_en' => 'Round-Trip Variant EN',
                        'name_fr' => 'Variante Aller-Retour FR',
                        'sku' => 'SKU-RT-001',
                        'regular_price' => 19.99,
                        'stock_quantity' => 7,
                        'weight' => 0.4,
                        'is_active' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // ── Step 2: Verify DB
        $product = Product::where('slug', 'round-trip-product')->first();
        $this->assertNotNull($product);

        $variant = ProductVariant::where(['product_id' => $product->id, 'sku' => 'SKU-RT-001'])->first();
        $this->assertNotNull($variant);
        $this->assertEquals('Round-Trip Variant EN', $variant->getTranslation('name', 'en'));
        $this->assertEquals('Variante Aller-Retour FR', $variant->getTranslation('name', 'fr'));

        // ── Step 3: Reload edit page and verify unpacked names
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(function (array $state) {
                $variantItems = collect($state['variants'] ?? []);
                $this->assertNotEmpty($variantItems, 'Variants should be loaded in edit form');

                $found = $variantItems->first(fn ($item) => ($item['sku'] ?? null) === 'SKU-RT-001');
                $this->assertNotNull($found, 'Round-trip variant should appear in form');

                $this->assertSame('Round-Trip Variant EN', $found['name_en'] ?? null);
                $this->assertSame('Variante Aller-Retour FR', $found['name_fr'] ?? null);
            });
    }

    /**
     * Omitted non-default names must not "carry over" across variants or edits.
     * A variant with only an EN name must show empty FR name on edit reload.
     */
    public function test_omitted_non_default_names_are_empty_on_edit_reload(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        // Create variant with only an EN name stored
        $product = Product::factory()->create([
            'name' => ['en' => 'EN-Only Product'],
            'slug' => 'en-only-product',
            'is_active' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => ['en' => 'EN-Only Variant'], // no FR
            'sku' => 'SKU-EN-ONLY-EDIT',
            'regular_price' => 10.00,
            'is_active' => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(function (array $state) {
                $variantItems = collect($state['variants'] ?? []);
                $found = $variantItems->first(fn ($item) => ($item['sku'] ?? null) === 'SKU-EN-ONLY-EDIT');

                $this->assertNotNull($found);
                $this->assertSame('EN-Only Variant', $found['name_en'] ?? null);
                // FR name should be empty/null, not carried over from elsewhere
                $this->assertEmpty($found['name_fr'] ?? '', 'FR name should be empty when not set');
            });
    }
}
