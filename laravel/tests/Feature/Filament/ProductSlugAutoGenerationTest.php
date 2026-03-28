<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\Product\Pages\CreateProduct;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Domains\Product\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSlugAutoGenerationTest extends TestCase
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

    // ── Slug fields exist in each tab ─────────────────────────────────────

    public function test_each_language_tab_has_a_slug_field(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    // ── Auto-generation for default locale ───────────────────────────────

    public function test_default_locale_name_auto_generates_slug_in_tab(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_en', 'My New Product')
            ->assertSet('data.slug_en', 'my-new-product');
    }

    public function test_default_locale_name_auto_generates_global_slug(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_en', 'My New Product')
            ->assertSet('data.slug', 'my-new-product');
    }

    public function test_slug_is_slugified_from_default_locale_name(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_en', 'Hello World & Stuff!')
            ->assertSet('data.slug_en', 'hello-world-stuff')
            ->assertSet('data.slug', 'hello-world-stuff');
    }

    // ── Auto-generation for non-default locale ────────────────────────────

    public function test_non_default_locale_name_auto_generates_slug_in_its_tab(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_de', 'Mein Produkt')
            ->assertSet('data.slug_de', 'mein-produkt');
    }

    public function test_non_default_locale_name_does_not_update_global_slug(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_de', 'Mein Produkt')
            ->assertSet('data.slug', '');
    }

    public function test_non_default_locale_does_not_overwrite_default_locale_slug(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_en', 'English Product')
            ->set('data.name_de', 'Deutsches Produkt')
            ->assertSet('data.slug', 'english-product')
            ->assertSet('data.slug_en', 'english-product')
            ->assertSet('data.slug_de', 'deutsches-produkt');
    }

    // ── Manual override detection per locale ─────────────────────────────

    public function test_manual_slug_edit_in_tab_prevents_auto_generation_for_that_locale(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_de', 'Original Name')
            ->set('data.slug_de', 'custom-manual-slug')
            // Now changing the name should NOT overwrite the manual slug
            ->set('data.name_de', 'Updated Name')
            ->assertSet('data.slug_de', 'custom-manual-slug');
    }

    public function test_manual_global_slug_edit_prevents_auto_generation_from_default_locale(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_en', 'Original Name')
            ->set('data.slug', 'custom-manual-slug')
            // Now changing the name should NOT overwrite the manual slug
            ->set('data.name_en', 'Updated Name')
            ->assertSet('data.slug', 'custom-manual-slug');
    }

    public function test_auto_generation_updates_tab_slug_when_it_matches_old_name(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name_de', 'Original Name')
            ->assertSet('data.slug_de', 'original-name')
            // Slug matches the auto-generated value, so it updates with new name
            ->set('data.name_de', 'Updated Name')
            ->assertSet('data.slug_de', 'updated-name');
    }

    // ── Empty slug gets auto-generated ────────────────────────────────────

    public function test_empty_tab_slug_is_auto_generated_from_name(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.slug_de', '')
            ->set('data.name_de', 'Auto Generated')
            ->assertSet('data.slug_de', 'auto-generated');
    }

    // ── Edit form pre-populates tab slug from global slug (default locale) ─

    public function test_edit_form_populates_default_locale_tab_slug_from_global_slug(): void
    {
        $product = Product::factory()->create([
            'name' => ['en' => 'Existing Product', 'de' => 'Vorhandenes Produkt'],
            'slug' => 'existing-product',
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(['slug_en' => 'existing-product']);
    }
}
