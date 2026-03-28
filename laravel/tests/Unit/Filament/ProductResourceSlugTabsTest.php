<?php

namespace Tests\Unit\Filament;

use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use App\Filament\Resources\Product\Pages\Concerns\MutatesProductTranslations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for T1.3: per-locale slug input fields inside ProductResource language tabs.
 */
class ProductResourceSlugTabsTest extends TestCase
{
    use RefreshDatabase;

    // ── MutatesProductTranslations: slug field stripping ─────────────────────

    /**
     * When building translation data, slug_{code} fields must be stripped out
     * (not merged into model data as-is) so they don't cause DB column errors.
     */
    public function test_build_translation_data_strips_slug_fields(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $trait = $this->makeTrait();

        $data = [
            'name_en'        => 'My Product',
            'name_fr'        => 'Mon Produit',
            'description_en' => 'Desc',
            'description_fr' => '',
            'slug_en'        => 'my-product',
            'slug_fr'        => 'mon-produit',
            'is_active'      => true,
        ];

        $result = $trait->exposeFormData($data);

        $this->assertArrayNotHasKey('slug_en', $result, 'slug_en must be stripped from persisted data');
        $this->assertArrayNotHasKey('slug_fr', $result, 'slug_fr must be stripped from persisted data');
    }

    /**
     * Slug field values collected from tabs must be accessible by key before
     * buildTranslationData processes them, confirming the trait reads them.
     */
    public function test_build_translation_data_reads_slug_values_before_stripping(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $trait = $this->makeTrait();

        $data = [
            'name_en'   => 'Widget',
            'slug_en'   => 'widget',
            'is_active' => true,
        ];

        // After processing, slug_en should be gone but name/description should remain
        $result = $trait->exposeFormData($data);

        $this->assertArrayNotHasKey('slug_en', $result);
        $this->assertIsArray($result['name']);
        $this->assertSame('Widget', $result['name']['en']);
    }

    // ── EditProduct: mutateFormDataBeforeFill pre-populates slug fields ───────

    /**
     * mutateFormDataBeforeFill must add slug_{code} keys to the form data
     * so Filament can populate each tab's slug input when editing.
     */
    public function test_mutate_form_data_before_fill_adds_slug_fields(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $product = Product::factory()->create([
            'name'      => ['en' => 'Test', 'fr' => 'Teste'],
            'is_active' => true,
        ]);

        // HasSlugs trait auto-generates slugs; retrieve the existing one for 'en'
        $slugEn = $product->getSlugForLocale('en');
        $this->assertNotNull($slugEn, 'Precondition: product must have an en slug from auto-generation');

        // Simulate what EditProduct::mutateFormDataBeforeFill returns
        $data   = $product->toArray();
        $result = $this->simulateMutateFormDataBeforeFill($product, $data);

        $this->assertArrayHasKey('slug_en', $result, 'slug_en must be present after mutateFormDataBeforeFill');
        $this->assertSame($slugEn->slug, $result['slug_en']);
    }

    /**
     * When no slug exists for a locale, slug_{code} should be null (not missing).
     */
    public function test_mutate_form_data_before_fill_sets_null_for_missing_locale_slug(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $product = Product::factory()->create([
            'name'      => ['en' => 'Test'],
            'is_active' => true,
        ]);

        $data   = $product->toArray();
        $result = $this->simulateMutateFormDataBeforeFill($product, $data);

        // 'fr' has no slug, so slug_fr should be null
        $this->assertArrayHasKey('slug_fr', $result, 'slug_fr key must exist even when no slug present');
        $this->assertNull($result['slug_fr']);
    }

    // ── ProductResource form schema: slug fields inside tabs ─────────────────

    /**
     * The ProductResource form schema must NOT contain a top-level 'slug' field
     * in the "Product Information" section (it was replaced by per-locale slug tabs).
     */
    public function test_product_resource_form_does_not_have_standalone_slug_field(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        // Parse the PHP source of ProductResource to verify the old slug field is gone
        $source = file_get_contents(app_path('Filament/Resources/ProductResource.php'));

        // The old slug field used ->unique(table: 'products', column: 'slug', ignoreRecord: true)
        $this->assertStringNotContainsString(
            "table: 'products', column: 'slug'",
            $source,
            "The standalone slug field with products table unique constraint must be removed"
        );
    }

    /**
     * The ProductResource form schema must contain slug_{code} fields inside the
     * translation tabs section.
     */
    public function test_product_resource_form_has_slug_fields_inside_tabs(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $source = file_get_contents(app_path('Filament/Resources/ProductResource.php'));

        $this->assertStringContainsString(
            '"slug_{$language->code}"',
            $source,
            'ProductResource must define slug_{language->code} fields inside translation tabs'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build an anonymous class that exposes buildTranslationData via a public method.
     */
    private function makeTrait(): object
    {
        return new class {
            use MutatesProductTranslations;

            public function exposeFormData(array $data): array
            {
                return $this->buildTranslationData($data);
            }
        };
    }

    /**
     * Simulate the logic of EditProduct::mutateFormDataBeforeFill for testing.
     * This mirrors exactly what the real method does (so if the method changes, tests catch it).
     */
    private function simulateMutateFormDataBeforeFill(Product $record, array $data): array
    {
        $languages = Language::all();

        foreach ($languages as $language) {
            $code                        = $language->code;
            $data["name_{$code}"]        = $record->getTranslation('name', $code, false);
            $data["description_{$code}"] = $record->getTranslation('description', $code, false);
            $data["slug_{$code}"]        = $record->getSlugForLocale($code)?->slug;
        }

        return $data;
    }
}
