<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSlugPrePopulationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Language $langEn;
    private Language $langDe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        $this->langEn = Language::factory()->default()->create([
            'code' => 'en',
            'name' => 'English',
        ]);

        $this->langDe = Language::factory()->create([
            'code' => 'de',
            'name' => 'German',
        ]);
    }

    private function createProductWithVariant(array $attributes = []): Product
    {
        $product = Product::factory()->create(array_merge([
            'name'      => ['en' => 'Test Product', 'de' => 'Testprodukt'],
            'slug'      => 'test-product',
            'is_active' => true,
        ], $attributes));

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        return $product;
    }

    // ── Form field existence ───────────────────────────────────────────────

    /** @test */
    public function test_edit_form_has_slug_field_for_each_locale(): void
    {
        $product = $this->createProductWithVariant();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    // ── Pre-population from slugs table ───────────────────────────────────

    /** @test */
    public function test_edit_form_pre_populates_slug_fields_from_slugs_table(): void
    {
        $product = $this->createProductWithVariant([
            'name' => ['en' => 'My Product', 'de' => 'Mein Produkt'],
            'slug' => 'my-product',
        ]);

        // Override the auto-created slugs with specific test values
        $product->slugs()->where('locale', 'en')->update(['slug' => 'my-custom-en-slug']);
        $product->slugs()->where('locale', 'de')->update(['slug' => 'mein-benutzerdefinierter-slug']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'my-custom-en-slug',
                'slug_de' => 'mein-benutzerdefinierter-slug',
            ]);
    }

    /** @test */
    public function test_edit_form_slug_field_is_empty_when_no_slug_entry_exists_for_locale(): void
    {
        $product = $this->createProductWithVariant([
            'name' => ['en' => 'Partial Product', 'de' => ''],
            'slug' => 'partial-product',
        ]);

        // Remove the German slug so it doesn't exist for this locale
        $product->slugs()->where('locale', 'de')->delete();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertNull($data['slug_de'] ?? null));
    }

    /** @test */
    public function test_slug_values_are_preserved_when_form_is_reopened(): void
    {
        $product = $this->createProductWithVariant([
            'name' => ['en' => 'Stable Product', 'de' => 'Stabiles Produkt'],
            'slug' => 'stable-product',
        ]);

        $product->slugs()->where('locale', 'en')->update(['slug' => 'stable-product-en']);
        $product->slugs()->where('locale', 'de')->update(['slug' => 'stable-product-de']);

        // Open form the first time
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'stable-product-en',
                'slug_de' => 'stable-product-de',
            ]);

        // Open form a second time – values must be identical
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'stable-product-en',
                'slug_de' => 'stable-product-de',
            ]);
    }

    /** @test */
    public function test_pre_population_works_for_all_active_languages(): void
    {
        $langFr = Language::factory()->create([
            'code' => 'fr',
            'name' => 'French',
        ]);

        $product = $this->createProductWithVariant([
            'name' => ['en' => 'Multi Lang Product', 'de' => 'Mehrsprachiges Produkt', 'fr' => 'Produit multilingue'],
            'slug' => 'multi-lang-product',
        ]);

        // Ensure French slug exists
        if (! $product->getSlugForLocale('fr')) {
            $product->slugs()->create([
                'sluggable_type' => Product::class,
                'sluggable_id'   => $product->id,
                'locale'         => 'fr',
                'slug'           => 'produit-multilingue',
            ]);
        }

        $product->slugs()->where('locale', 'en')->update(['slug' => 'multi-lang-product-en']);
        $product->slugs()->where('locale', 'de')->update(['slug' => 'mehrsprachiges-produkt-de']);
        $product->slugs()->where('locale', 'fr')->update(['slug' => 'produit-multilingue-fr']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'multi-lang-product-en',
                'slug_de' => 'mehrsprachiges-produkt-de',
                'slug_fr' => 'produit-multilingue-fr',
            ]);
    }
}
