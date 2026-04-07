<?php

namespace Tests\Feature\Filament;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Product\Models\Product;
use App\Domains\Slug\Models\Slug;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\ManufacturerResource\Pages\CreateManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\EditManufacturer;
use App\Filament\Resources\Product\Pages\CreateProduct;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlugValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Reject duplicate slugs from slugs table – Product ─────────────────

    public function test_create_product_rejects_slug_already_in_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Create an orphan slug entry (no real model needed) to occupy 'taken-slug'
        Slug::create([
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => 99999,
            'locale'         => 'en',
            'slug'           => 'taken-slug',
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'  => 'Some Product',
                'slug_en'  => 'taken-slug',
                'variants' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── Reject duplicate slugs from slugs table – Category ────────────────

    public function test_create_category_rejects_slug_already_in_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Create an orphan slug entry (no real model needed) to occupy 'shared-slug'
        Slug::create([
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => 99999,
            'locale'         => 'en',
            'slug'           => 'shared-slug',
        ]);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => ['en' => 'Some Category'],
                'slug_en' => 'shared-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── Reject duplicate slugs from slugs table – Manufacturer ────────────

    public function test_create_manufacturer_rejects_slug_already_in_slugs_table(): void
    {
        // Create product BEFORE language so HasSlugs doesn't auto-create a slug entry
        $product = Product::factory()->create(['slug' => 'global-slug']);

        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Now manually insert the slug entry — no duplicate conflict
        Slug::create([
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => 'en',
            'slug'           => 'global-slug',
        ]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'Some Manufacturer',
                'slug_en' => 'global-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── alphaDash validation – Product ────────────────────────────────────

    public function test_create_product_slug_rejects_spaces_and_special_chars(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'  => 'Bad Product',
                'slug_en'  => 'invalid slug!',
                'variants' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_create_product_slug_accepts_hyphens_and_underscores(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name_en'  => 'Good Product',
                'slug'     => 'good-product_v2',
                'variants' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors(['slug']);
    }

    // ── alphaDash validation – Category ───────────────────────────────────

    public function test_create_category_slug_rejects_spaces_and_special_chars(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'Bad Category'],
                'slug_en' => 'bad slug@here',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── alphaDash validation – Manufacturer ───────────────────────────────

    public function test_create_manufacturer_slug_rejects_spaces_and_special_chars(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'Bad Manufacturer',
                'slug_en' => 'bad slug!',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── Edit allows own slug from slugs table – Manufacturer ──────────────

    public function test_edit_manufacturer_allows_own_slug_from_slugs_table(): void
    {
        // Create manufacturer BEFORE language so HasSlugs skips auto-slug creation
        $manufacturer = Manufacturer::factory()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Slug::create([
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'locale'         => 'en',
            'slug'           => 'acme-corp',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm([
                'name_en' => 'Acme Corp Updated',
                'slug_en' => 'acme-corp',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug_en']);
    }

    // ── Edit allows own slug from slugs table – Category ──────────────────

    public function test_edit_category_allows_own_slug_from_slugs_table(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Tech Category'],
            'slug' => 'tech-category',
        ]);
        Slug::create([
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'tech-category',
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => ['en' => 'Tech Category'],
                'slug' => 'tech-category',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug']);
    }

    // ── Edit allows own slug from slugs table – Product ───────────────────

    public function test_edit_product_allows_own_slug_from_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Factory auto-creates the slug entry via HasSlugs; no need to create it manually
        $product = Product::factory()->active()->create(['slug' => 'my-product']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'slug_en' => 'my-product',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug_en']);
    }

    // ── Edit rejects slug that belongs to a different record ──────────────

    public function test_edit_manufacturer_rejects_slug_from_another_slugs_record(): void
    {
        // Create manufacturers BEFORE language so HasSlugs skips auto-slug creation
        $manufacturer1 = Manufacturer::factory()->create(['slug' => 'brand-one']);
        $manufacturer2 = Manufacturer::factory()->create(['slug' => 'brand-two']);

        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Slug::create([
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer1->id,
            'locale'         => 'en',
            'slug'           => 'brand-one',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer2->getRouteKey()])
            ->fillForm([
                'name_en' => 'Brand Two',
                'slug_en' => 'brand-one', // trying to steal brand-one's slug
            ])
            ->call('save')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── Cross-resource slug uniqueness ────────────────────────────────────

    public function test_manufacturer_rejects_slug_already_used_by_product(): void
    {
        // Create product BEFORE language so HasSlugs skips auto-slug creation
        $product = Product::factory()->create(['slug' => 'universal-slug']);

        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Slug::create([
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => 'en',
            'slug'           => 'universal-slug',
        ]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'New Manufacturer',
                'slug_en' => 'universal-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }
}
