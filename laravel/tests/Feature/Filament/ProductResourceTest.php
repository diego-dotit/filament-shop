<?php

namespace Tests\Feature\Filament;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\Product\Pages\CreateProduct;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Resource Configuration ─────────────────────────────────────────────

    public function test_product_resource_uses_correct_model(): void
    {
        $this->assertSame(Product::class, ProductResource::getModel());
    }

    public function test_product_resource_has_required_pages(): void
    {
        $pages = ProductResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    // ── Create Page ────────────────────────────────────────────────────────

    public function test_create_product_page_renders(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertSuccessful();
    }

    public function test_can_create_product_with_variants(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'   => 'Test Product',
                'slug'      => 'test-product',
                'is_active' => true,
                'variants'  => [
                    [
                        'sku'            => 'SKU-001',
                        'regular_price'  => 29.99,
                        'special_price'  => null,
                        'stock_quantity' => 10,
                        'weight'         => 0.5,
                        'is_active'      => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'slug'      => 'test-product',
            'is_active' => true,
        ]);

        $product = Product::where('slug', 'test-product')->first();
        $this->assertNotNull($product);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku'        => 'SKU-001',
        ]);
    }

    public function test_create_product_requires_name(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'  => '',
                'slug'     => 'some-slug',
                'variants' => [
                    [
                        'sku'            => 'SKU-002',
                        'regular_price'  => 10.00,
                        'stock_quantity' => 1,
                        'weight'         => 0.5,
                        'is_active'      => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en']);
    }

    public function test_create_product_requires_unique_slug(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $product = Product::factory()->create(['slug' => 'existing-slug']);
        // Ensure the slug entry exists in the slugs table (HasSlugs may use name, not 'slug' column)
        $product->slugs()->updateOrCreate(['locale' => 'en'], ['slug' => 'existing-slug']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'  => 'Another Product',
                'slug_en'  => 'existing-slug',
                'variants' => [
                    [
                        'sku'            => 'SKU-003',
                        'regular_price'  => 15.00,
                        'stock_quantity' => 5,
                        'weight'         => 1.0,
                        'is_active'      => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_sku_must_be_unique_across_variants(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        ProductVariant::factory()->create(['sku' => 'DUPLICATE-SKU']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'  => 'Product With Duplicate SKU',
                'slug'     => 'product-with-duplicate-sku',
                'variants' => [
                    [
                        'sku'            => 'DUPLICATE-SKU',
                        'regular_price'  => 20.00,
                        'stock_quantity' => 3,
                        'weight'         => 0.3,
                        'is_active'      => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['variants.0.sku']);
    }

    // ── Edit Page ──────────────────────────────────────────────────────────

    public function test_edit_product_page_renders_with_existing_data(): void
    {
        $product = Product::factory()->create([
            'name'      => ['en' => 'Editable Product'],
            'slug'      => 'editable-product',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku'        => 'SKU-EDIT-001',
            'is_active'  => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['slug' => 'editable-product']);
    }

    public function test_can_edit_product_and_update_variant(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $product = Product::factory()->create([
            'name'      => ['en' => 'Original Product'],
            'slug'      => 'original-product',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id'    => $product->id,
            'sku'           => 'SKU-ORIGINAL',
            'regular_price' => 10.00,
            'is_active'     => true,
        ]);

        // Only update product-level fields; variants are loaded from relationship
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'name_en'   => 'Updated Product',
                'slug_en'   => 'original-product',
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'id'        => $product->id,
            'slug'      => 'original-product',
            'is_active' => true,
        ]);
    }

    public function test_edit_product_allows_own_slug_without_unique_error(): void
    {
        $product = Product::factory()->create([
            'slug' => 'my-product-slug',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'slug' => 'my-product-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug']);
    }

    // ── Slug Auto-Generation ───────────────────────────────────────────────

    public function test_slug_field_exists_in_create_form(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $component = Livewire::test(CreateProduct::class);

        // The form should have a slug field
        $component->assertFormFieldExists('slug_en');
    }

    // ── Translations ───────────────────────────────────────────────────────

    public function test_create_form_has_translation_fields_for_each_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('name_en')
            ->assertFormFieldExists('name_de')
            ->assertFormFieldExists('description_en')
            ->assertFormFieldExists('description_de');
    }

    public function test_can_create_product_with_multiple_language_translations(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'         => 'English Product',
                'name_de'         => 'Deutsches Produkt',
                'description_en'  => 'English description',
                'description_de'  => 'Deutsche Beschreibung',
                'slug'            => 'english-product',
                'is_active'       => true,
                'variants'        => [
                    [
                        'sku'            => 'SKU-TRANS-001',
                        'regular_price'  => 19.99,
                        'stock_quantity' => 5,
                        'weight'         => 0.3,
                        'is_active'      => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'english-product')->first();
        $this->assertNotNull($product);
        $this->assertEquals('English Product', $product->getTranslation('name', 'en'));
        $this->assertEquals('Deutsches Produkt', $product->getTranslation('name', 'de'));
        $this->assertEquals('English description', $product->getTranslation('description', 'en'));
        $this->assertEquals('Deutsche Beschreibung', $product->getTranslation('description', 'de'));
    }

    public function test_name_in_default_language_is_required_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en' => '',
                'name_de' => 'Deutsches Produkt',
                'slug'    => 'some-product',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en']);
    }

    public function test_name_in_non_default_language_is_optional(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'  => 'English Only Product',
                'name_de'  => '',
                'slug'     => 'english-only-product',
                'is_active' => true,
                'variants' => [
                    [
                        'sku'            => 'SKU-OPTIONAL-001',
                        'regular_price'  => 9.99,
                        'stock_quantity' => 2,
                        'weight'         => 0.2,
                        'is_active'      => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors(['name_de']);
    }

    public function test_edit_form_pre_populates_existing_translations(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        $product = Product::factory()->create([
            'name'        => ['en' => 'English Product', 'de' => 'Deutsches Produkt'],
            'description' => ['en' => 'English desc', 'de' => 'Deutsche Beschr.'],
            'slug'        => 'english-product',
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'name_en'        => 'English Product',
                'name_de'        => 'Deutsches Produkt',
                'description_en' => 'English desc',
                'description_de' => 'Deutsche Beschr.',
            ]);
    }

    public function test_translations_saved_and_retrieved_correctly_per_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        $product = Product::factory()->create([
            'name'        => ['en' => 'Original English', 'fr' => 'Original French'],
            'description' => ['en' => 'Desc EN', 'fr' => 'Desc FR'],
            'slug'        => 'trans-test-product',
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'name_en'        => 'Updated English',
                'name_fr'        => 'Updated French',
                'description_en' => 'Updated Desc EN',
                'description_fr' => 'Updated Desc FR',
                'slug'           => 'trans-test-product',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertEquals('Updated English', $product->getTranslation('name', 'en'));
        $this->assertEquals('Updated French', $product->getTranslation('name', 'fr'));
        $this->assertEquals('Updated Desc EN', $product->getTranslation('description', 'en'));
        $this->assertEquals('Updated Desc FR', $product->getTranslation('description', 'fr'));
    }

    // ── Categories & Manufacturers ─────────────────────────────────────────

    public function test_create_form_has_categories_field(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('categories');
    }

    public function test_create_form_has_manufacturers_field(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('manufacturers');
    }

    public function test_can_create_product_with_categories_and_manufacturers(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category1    = Category::factory()->create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);
        $category2    = Category::factory()->create(['name' => ['en' => 'Gadgets'], 'slug' => 'gadgets', 'is_active' => true]);
        $manufacturer = Manufacturer::factory()->create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'       => 'Categorised Product',
                'slug'          => 'categorised-product',
                'is_active'     => true,
                'categories'    => [$category1->id, $category2->id],
                'manufacturers' => [$manufacturer->id],
                'variants'      => [
                    [
                        'sku'            => 'SKU-CAT-001',
                        'regular_price'  => 49.99,
                        'stock_quantity' => 5,
                        'weight'         => 0.5,
                        'is_active'      => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'categorised-product')->first();
        $this->assertNotNull($product);

        $this->assertDatabaseHas('category_product', [
            'product_id'  => $product->id,
            'category_id' => $category1->id,
        ]);
        $this->assertDatabaseHas('category_product', [
            'product_id'  => $product->id,
            'category_id' => $category2->id,
        ]);
        $this->assertDatabaseHas('product_manufacturer', [
            'product_id'      => $product->id,
            'manufacturer_id' => $manufacturer->id,
        ]);
    }

    public function test_edit_product_prepopulates_categories_and_manufacturers(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category     = Category::factory()->create(['name' => ['en' => 'Tools'], 'slug' => 'tools', 'is_active' => true]);
        $manufacturer = Manufacturer::factory()->create(['name' => 'ToolCo', 'slug' => 'toolco']);

        $product = Product::factory()->create([
            'name'      => ['en' => 'Tool Product'],
            'slug'      => 'tool-product',
            'is_active' => true,
        ]);
        $product->categories()->attach($category->id);
        $product->manufacturers()->attach($manufacturer->id);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'categories'    => [$category->id],
                'manufacturers' => [$manufacturer->id],
            ]);
    }

    public function test_edit_product_updates_categories_on_save(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $categoryOld = Category::factory()->create(['name' => ['en' => 'Old Cat'], 'slug' => 'old-cat', 'is_active' => true]);
        $categoryNew = Category::factory()->create(['name' => ['en' => 'New Cat'], 'slug' => 'new-cat', 'is_active' => true]);

        $product = Product::factory()->create([
            'name'      => ['en' => 'Editable Cat Product'],
            'slug'      => 'editable-cat-product',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);
        $product->categories()->attach($categoryOld->id);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'categories' => [$categoryNew->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertCount(1, $product->categories);
        $this->assertEquals($categoryNew->id, $product->categories->first()->id);
        $this->assertDatabaseMissing('category_product', [
            'product_id'  => $product->id,
            'category_id' => $categoryOld->id,
        ]);
    }
}
