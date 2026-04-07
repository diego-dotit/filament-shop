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

class ProductMetaPrePopulationTest extends TestCase
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

    private function createProductWithMeta(array $attributes = []): Product
    {
        $product = Product::factory()->create(array_merge([
            'name'             => ['en' => 'Test Product', 'de' => 'Testprodukt'],
            'slug'             => 'test-product',
            'is_active'        => true,
            'meta_title'       => ['en' => 'Meta Title EN', 'de' => 'Meta Titel DE'],
            'meta_description' => ['en' => 'Meta Description EN', 'de' => 'Meta Beschreibung DE'],
            'meta_keywords'    => ['en' => 'keyword1, keyword2', 'de' => 'schlüsselwort1, schlüsselwort2'],
        ], $attributes));

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        return $product;
    }

    // ── Pre-population: meta_title ─────────────────────────────────────────

    /** @test */
    public function test_edit_form_pre_populates_meta_title_for_each_locale(): void
    {
        $product = $this->createProductWithMeta();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'meta_title_en' => 'Meta Title EN',
                'meta_title_de' => 'Meta Titel DE',
            ]);
    }

    // ── Pre-population: meta_description ──────────────────────────────────

    /** @test */
    public function test_edit_form_pre_populates_meta_description_for_each_locale(): void
    {
        $product = $this->createProductWithMeta();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'meta_description_en' => 'Meta Description EN',
                'meta_description_de' => 'Meta Beschreibung DE',
            ]);
    }

    // ── Pre-population: meta_keywords ─────────────────────────────────────

    /** @test */
    public function test_edit_form_pre_populates_meta_keywords_for_each_locale(): void
    {
        $product = $this->createProductWithMeta();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'meta_keywords_en' => 'keyword1, keyword2',
                'meta_keywords_de' => 'schlüsselwort1, schlüsselwort2',
            ]);
    }

    // ── Save direction: meta fields are persisted correctly ───────────────

    /** @test */
    public function test_saving_edit_form_persists_meta_fields_as_json_translations(): void
    {
        $product = $this->createProductWithMeta();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'meta_title_en'       => 'Updated Meta EN',
                'meta_title_de'       => 'Aktualisiert DE',
                'meta_description_en' => 'Updated Desc EN',
                'meta_description_de' => 'Aktualisiert Beschreibung DE',
                'meta_keywords_en'    => 'new, keywords',
                'meta_keywords_de'    => 'neue, schlüsselwörter',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        $this->assertSame('Updated Meta EN', $product->getTranslation('meta_title', 'en'));
        $this->assertSame('Aktualisiert DE', $product->getTranslation('meta_title', 'de'));
        $this->assertSame('Updated Desc EN', $product->getTranslation('meta_description', 'en'));
        $this->assertSame('Aktualisiert Beschreibung DE', $product->getTranslation('meta_description', 'de'));
        $this->assertSame('new, keywords', $product->getTranslation('meta_keywords', 'en'));
        $this->assertSame('neue, schlüsselwörter', $product->getTranslation('meta_keywords', 'de'));
    }

    // ── Empty meta fields are handled gracefully ──────────────────────────

    /** @test */
    public function test_edit_form_pre_populates_empty_string_when_no_meta_translation_exists(): void
    {
        $product = Product::factory()->create([
            'name'             => ['en' => 'No Meta Product', 'de' => 'Kein Meta Produkt'],
            'slug'             => 'no-meta-product',
            'is_active'        => true,
            'meta_title'       => null,
            'meta_description' => null,
            'meta_keywords'    => null,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(function (array $data) {
                $this->assertEmpty($data['meta_title_en'] ?? '');
                $this->assertEmpty($data['meta_title_de'] ?? '');
                $this->assertEmpty($data['meta_description_en'] ?? '');
                $this->assertEmpty($data['meta_description_de'] ?? '');
                $this->assertEmpty($data['meta_keywords_en'] ?? '');
                $this->assertEmpty($data['meta_keywords_de'] ?? '');
            });
    }
}
