<?php

namespace Tests\Unit\Resources;

use App\Domains\Category\Models\Category;
use App\Domains\Product\Models\Product;
use App\Http\Resources\Api\Category\CategoryResource;
use App\Http\Resources\Api\Product\ProductResource;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Verifies that language translation is applied consistently across all
 * translatable fields in ProductResource and CategoryResource.
 *
 * Acceptance criteria:
 *   - Correct language returned when lang attribute present on request
 *   - Fallback to Spatie fallback_locale when model lacks translation for requested language
 *   - All translatable fields (name + description for Product, name for Category)
 *     apply the same language resolution consistently
 */
class LanguageTranslationTest extends TestCase
{
    // ── ProductResource ───────────────────────────────────────────────────────

    public function test_product_resource_translates_all_fields_with_request_lang(): void
    {
        $product = $this->makeProduct();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new ProductResource($product);
        $data = $resource->toArray($request);

        // Both name AND description must use the French translation
        $this->assertSame('Produit Test', $data['name']);
        $this->assertSame('Description Test', $data['description']);
    }

    public function test_product_resource_falls_back_to_spatie_fallback_locale_when_translation_missing(): void
    {
        // Product only has 'en' translation — no 'de'
        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'test-product',
            'name'        => json_encode(['en' => 'English Product']),
            'description' => json_encode(['en' => 'English Description']),
            'is_active'   => true,
        ]);

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'de'; // 'de' not in model → Spatie falls back to fallback_locale ('en')
        $request->attributes->set('lang', $langStub);

        $resource = new ProductResource($product);
        $data = $resource->toArray($request);

        // Should fall back to 'en' (Spatie fallback_locale = 'en')
        $this->assertSame('English Product', $data['name']);
        $this->assertSame('English Description', $data['description']);
    }

    public function test_product_resource_fallback_applies_to_all_translatable_fields_consistently(): void
    {
        // Product has 'en' and 'fr' but description missing 'fr'
        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'partial-product',
            // name has 'fr', description only has 'en'
            'name'        => json_encode(['en' => 'English Name', 'fr' => 'Nom Français']),
            'description' => json_encode(['en' => 'English Description']),
            'is_active'   => true,
        ]);

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new ProductResource($product);
        $data = $resource->toArray($request);

        // name has 'fr' → returns French
        $this->assertSame('Nom Français', $data['name']);
        // description has no 'fr' → Spatie falls back to 'en'
        $this->assertSame('English Description', $data['description']);
    }

    public function test_product_resource_uses_app_locale_when_no_lang_on_request(): void
    {
        $product = $this->makeProduct();

        // No lang attribute set on request
        $request = Request::create('/');

        $resource = new ProductResource($product);
        $data = $resource->toArray($request);

        // app()->getLocale() defaults to 'en' in test environment
        $this->assertSame('Test Product', $data['name']);
        $this->assertSame('Test Description', $data['description']);
    }

    // ── CategoryResource ──────────────────────────────────────────────────────

    public function test_category_resource_falls_back_to_spatie_fallback_locale_when_translation_missing(): void
    {
        // Category only has 'en' — no 'ja'
        $category = new Category();
        $category->setRawAttributes([
            'id'        => 1,
            'slug'      => 'electronics',
            'name'      => json_encode(['en' => 'Electronics']),
            'is_active' => true,
        ]);

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'ja'; // 'ja' not in model → falls back to 'en'
        $request->attributes->set('lang', $langStub);

        $resource = new CategoryResource($category);
        $data = $resource->toArray($request);

        // Spatie fallback_locale = 'en'
        $this->assertSame('Electronics', $data['name']);
    }

    public function test_category_resource_translates_name_with_request_lang(): void
    {
        $category = $this->makeCategory();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new CategoryResource($category);
        $data = $resource->toArray($request);

        $this->assertSame('Électronique', $data['name']);
    }

    public function test_category_resource_uses_app_locale_when_no_lang_on_request(): void
    {
        $category = $this->makeCategory();

        $request = Request::create('/');

        $resource = new CategoryResource($category);
        $data = $resource->toArray($request);

        $this->assertSame('Electronics', $data['name']);
    }

    // ── Consistency: same resolution logic in both resources ─────────────────

    public function test_product_and_category_resources_use_same_lang_resolution_logic(): void
    {
        $product  = $this->makeProduct();
        $category = $this->makeCategory();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $productData  = (new ProductResource($product))->toArray($request);
        $categoryData = (new CategoryResource($category))->toArray($request);

        // Both resolve to French
        $this->assertSame('Produit Test', $productData['name']);
        $this->assertSame('Électronique', $categoryData['name']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeProduct(): Product
    {
        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'test-product',
            'name'        => json_encode(['en' => 'Test Product', 'fr' => 'Produit Test']),
            'description' => json_encode(['en' => 'Test Description', 'fr' => 'Description Test']),
            'is_active'   => true,
        ]);

        return $product;
    }

    private function makeCategory(): Category
    {
        $category = new Category();
        $category->setRawAttributes([
            'id'        => 1,
            'slug'      => 'electronics',
            'name'      => json_encode(['en' => 'Electronics', 'fr' => 'Électronique']),
            'is_active' => true,
        ]);

        return $category;
    }
}
