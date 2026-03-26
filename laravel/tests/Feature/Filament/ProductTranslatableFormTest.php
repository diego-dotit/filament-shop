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

class ProductTranslatableFormTest extends TestCase
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

    // ── Form Field Existence ───────────────────────────────────────────────

    public function test_create_form_has_name_fields_per_language(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('name_en')
            ->assertFormFieldExists('name_de');
    }

    public function test_create_form_has_description_fields_per_language(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('description_en')
            ->assertFormFieldExists('description_de');
    }

    // ── Create with Translations ───────────────────────────────────────────

    public function test_creating_product_saves_translations_correctly(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'      => 'English Product Name',
                'name_de'      => 'Deutsches Produktname',
                'description_en' => 'English description',
                'description_de' => 'Deutsche Beschreibung',
                'slug'         => 'english-product-name',
                'is_active'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'english-product-name')->first();
        $this->assertNotNull($product);

        $this->assertSame('English Product Name', $product->getTranslation('name', 'en'));
        $this->assertSame('Deutsches Produktname', $product->getTranslation('name', 'de'));
        $this->assertSame('English description', $product->getTranslation('description', 'en'));
        $this->assertSame('Deutsche Beschreibung', $product->getTranslation('description', 'de'));
    }

    public function test_creating_without_non_default_language_name_succeeds(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'   => 'Only English Name',
                'name_de'   => '',
                'slug'      => 'only-english-name',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'only-english-name')->first();
        $this->assertNotNull($product);
        $this->assertSame('Only English Name', $product->getTranslation('name', 'en'));
    }

    // ── Validation ─────────────────────────────────────────────────────────

    public function test_create_requires_name_in_default_language(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'   => '',
                'name_de'   => 'Nur Deutsch',
                'slug'      => 'some-slug',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en']);
    }

    // ── Edit Pre-population ────────────────────────────────────────────────

    public function test_edit_form_pre_populates_translations(): void
    {
        $product = Product::factory()->create([
            'name'      => ['en' => 'Edit English', 'de' => 'Edit Deutsch'],
            'slug'      => 'edit-english',
            'description' => ['en' => 'Edit desc EN', 'de' => 'Edit Beschreibung DE'],
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'name_en'        => 'Edit English',
                'name_de'        => 'Edit Deutsch',
                'description_en' => 'Edit desc EN',
                'description_de' => 'Edit Beschreibung DE',
            ]);
    }

    public function test_editing_product_updates_translations_correctly(): void
    {
        $product = Product::factory()->create([
            'name'      => ['en' => 'Original English', 'de' => 'Original Deutsch'],
            'slug'      => 'original-english',
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active'  => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'name_en'   => 'Updated English',
                'name_de'   => 'Aktualisiert Deutsch',
                'slug'      => 'original-english',
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('Updated English', $product->getTranslation('name', 'en'));
        $this->assertSame('Aktualisiert Deutsch', $product->getTranslation('name', 'de'));
    }
}
