<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use App\Filament\Resources\Product\Pages\CreateProduct;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Domains\Product\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verifies that MutatesProductTranslations correctly converts per-locale
 * meta field inputs (meta_title_en, meta_description_de, etc.) into JSON
 * translation arrays before the Product model is persisted.
 */
class ProductMetaFieldsMutationTest extends TestCase
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

    public function test_creating_product_saves_meta_title_translations(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'        => 'Meta Test Product',
                'slug'           => 'meta-test-product',
                'is_active'      => true,
                'meta_title_en'  => 'SEO Title English',
                'meta_title_de'  => 'SEO Titel Deutsch',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'meta-test-product')->first();
        $this->assertNotNull($product, 'Product should be created');

        $this->assertSame('SEO Title English', $product->getTranslation('meta_title', 'en'));
        $this->assertSame('SEO Titel Deutsch', $product->getTranslation('meta_title', 'de'));
    }

    public function test_creating_product_saves_meta_description_translations(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'              => 'Meta Desc Product',
                'slug'                 => 'meta-desc-product',
                'is_active'            => true,
                'meta_description_en'  => 'SEO Description English',
                'meta_description_de'  => 'SEO Beschreibung Deutsch',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'meta-desc-product')->first();
        $this->assertNotNull($product, 'Product should be created');

        $this->assertSame('SEO Description English', $product->getTranslation('meta_description', 'en'));
        $this->assertSame('SEO Beschreibung Deutsch', $product->getTranslation('meta_description', 'de'));
    }

    public function test_creating_product_saves_meta_keywords_translations(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'            => 'Meta Keywords Product',
                'slug'               => 'meta-keywords-product',
                'is_active'          => true,
                'meta_keywords_en'   => 'keyword1, keyword2',
                'meta_keywords_de'   => 'Schlüsselwort1, Schlüsselwort2',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'meta-keywords-product')->first();
        $this->assertNotNull($product, 'Product should be created');

        $this->assertSame('keyword1, keyword2', $product->getTranslation('meta_keywords', 'en'));
        $this->assertSame('Schlüsselwort1, Schlüsselwort2', $product->getTranslation('meta_keywords', 'de'));
    }

    public function test_creating_product_with_all_meta_fields_saves_correctly(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'              => 'Full Meta Product',
                'description_en'       => 'Product description',
                'slug'                 => 'full-meta-product',
                'is_active'            => true,
                'meta_title_en'        => 'Full SEO Title',
                'meta_title_de'        => 'Vollständiger SEO-Titel',
                'meta_description_en'  => 'Full SEO Description',
                'meta_description_de'  => 'Vollständige SEO-Beschreibung',
                'meta_keywords_en'     => 'full, seo, keywords',
                'meta_keywords_de'     => 'voll, seo, Schlüsselwörter',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'full-meta-product')->first();
        $this->assertNotNull($product, 'Product should be created');

        $this->assertSame('Full SEO Title', $product->getTranslation('meta_title', 'en'));
        $this->assertSame('Vollständiger SEO-Titel', $product->getTranslation('meta_title', 'de'));
        $this->assertSame('Full SEO Description', $product->getTranslation('meta_description', 'en'));
        $this->assertSame('Vollständige SEO-Beschreibung', $product->getTranslation('meta_description', 'de'));
        $this->assertSame('full, seo, keywords', $product->getTranslation('meta_keywords', 'en'));
        $this->assertSame('voll, seo, Schlüsselwörter', $product->getTranslation('meta_keywords', 'de'));
    }

    public function test_editing_product_saves_meta_title_translations(): void
    {
        $product = Product::factory()->create([
            'name'      => ['en' => 'Edit Meta Product'],
            'slug'      => 'edit-meta-product',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'name_en'       => 'Edit Meta Product',
                'slug'          => 'edit-meta-product',
                'is_active'     => true,
                'meta_title_en' => 'Updated SEO Title',
                'meta_title_de' => 'Aktualisierter SEO-Titel',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('Updated SEO Title', $product->getTranslation('meta_title', 'en'));
        $this->assertSame('Aktualisierter SEO-Titel', $product->getTranslation('meta_title', 'de'));
    }
}
