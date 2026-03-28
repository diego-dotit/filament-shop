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

class ProductSlugManualOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Language $defaultLanguage;
    private Language $secondLanguage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        $this->defaultLanguage = Language::factory()->default()->create([
            'code' => 'en',
            'name' => 'English',
        ]);

        $this->secondLanguage = Language::factory()->create([
            'code' => 'de',
            'name' => 'German',
        ]);
    }

    // ── Form Field Existence ──────────────────────────────────────────────

    public function test_create_form_has_slug_field_per_language(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    // ── Create – Slug Persistence ─────────────────────────────────────────

    public function test_creating_product_persists_manual_slugs_to_slugs_table(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'   => 'Test Product',
                'slug_en'   => 'custom-en-slug',
                'name_de'   => 'Testprodukt',
                'slug_de'   => 'custom-de-slug',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // products.slug is derived from the default locale (en) slug field
        $product = Product::where('slug', 'custom-en-slug')->first();
        $this->assertNotNull($product);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => 'en',
            'slug'           => 'custom-en-slug',
        ]);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => 'de',
            'slug'           => 'custom-de-slug',
        ]);
    }

    // ── Edit – Slug Pre-population ────────────────────────────────────────

    public function test_edit_form_prepopulates_slug_fields_from_slugs_table(): void
    {
        $product = Product::factory()->create([
            'name'      => ['en' => 'My Product', 'de' => 'Mein Produkt'],
            'slug'      => 'my-product',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        // Use updateOrCreate to handle HasSlugs auto-generated slugs for these locales
        $product->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'my-product-en'],
        );

        $product->slugs()->updateOrCreate(
            ['locale' => 'de'],
            ['slug'   => 'mein-produkt-de'],
        );

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'my-product-en',
                'slug_de' => 'mein-produkt-de',
            ]);
    }

    // ── Edit – Slug Persistence ───────────────────────────────────────────

    public function test_editing_product_updates_existing_slug_in_slugs_table(): void
    {
        $product = Product::factory()->create([
            'name'      => ['en' => 'Original Product'],
            'slug'      => 'original-product',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        // Use updateOrCreate to handle HasSlugs auto-generated slug for 'en'
        $product->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'original-product-en'],
        );

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'slug_en' => 'updated-product-en',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => 'en',
            'slug'           => 'updated-product-en',
        ]);

        $this->assertDatabaseMissing('slugs', [
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => 'en',
            'slug'           => 'original-product-en',
        ]);
    }

    // ── Validation – Slug Uniqueness ──────────────────────────────────────

    public function test_slug_uniqueness_validated_against_slugs_table(): void
    {
        // Create another product with a slug already in the slugs table
        $otherProduct = Product::factory()->create([
            'name' => ['en' => 'Other Product'],
            'slug' => 'other-product',
        ]);
        // Use updateOrCreate to handle HasSlugs auto-generated slug for 'en'
        $otherProduct->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'taken-slug'],
        );

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'   => 'New Product',
                'slug_en'   => 'taken-slug',
                'slug'      => 'new-product',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_edit_allows_saving_product_with_own_existing_slug(): void
    {
        $product = Product::factory()->create([
            'name'      => ['en' => 'My Product'],
            'slug'      => 'my-product',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        // Use updateOrCreate to handle HasSlugs auto-generated slug for 'en'
        $product->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'my-product-slug'],
        );

        // Saving with the same slug should NOT produce a validation error
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'slug_en' => 'my-product-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug_en']);
    }
}
