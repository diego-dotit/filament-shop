<?php

namespace Tests\Feature;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Product\Models\Product;
use App\Domains\Slug\Models\Slug;
use Database\Seeders\SlugPopulationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugPopulationSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $defaultLocale = 'en';

    protected function setUp(): void
    {
        parent::setUp();

        // Create default language
        Language::create([
            'code'       => $this->defaultLocale,
            'name'       => 'English',
            'is_default' => true,
        ]);
    }

    public function test_seeder_populates_slugs_for_products(): void
    {
        Product::create(['name' => ['en' => 'Test Product'], 'slug' => 'test-product', 'is_active' => true]);

        $this->seed(SlugPopulationSeeder::class);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Product::class,
            'locale'         => $this->defaultLocale,
            'slug'           => 'test-product',
        ]);
    }

    public function test_seeder_populates_slugs_for_categories(): void
    {
        Category::create(['name' => ['en' => 'Test Category'], 'slug' => 'test-category', 'is_active' => true]);

        $this->seed(SlugPopulationSeeder::class);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Category::class,
            'locale'         => $this->defaultLocale,
            'slug'           => 'test-category',
        ]);
    }

    public function test_seeder_populates_slugs_for_manufacturers(): void
    {
        Manufacturer::create(['name' => 'Test Manufacturer', 'slug' => 'test-manufacturer']);

        $this->seed(SlugPopulationSeeder::class);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'locale'         => $this->defaultLocale,
            'slug'           => 'test-manufacturer',
        ]);
    }

    public function test_seeder_is_idempotent_when_run_twice(): void
    {
        Product::create(['name' => ['en' => 'Widget'], 'slug' => 'widget', 'is_active' => true]);

        $this->seed(SlugPopulationSeeder::class);
        $this->seed(SlugPopulationSeeder::class);

        $count = Slug::where('slug', 'widget')->count();
        $this->assertSame(1, $count, 'Running seeder twice should not create duplicate slug entries');
    }

    public function test_seeder_uses_default_language_locale(): void
    {
        Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
        Product::create(['name' => ['en' => 'Produit'], 'slug' => 'produit', 'is_active' => true]);

        $this->seed(SlugPopulationSeeder::class);

        $slug = Slug::where('slug', 'produit')->first();
        $this->assertNotNull($slug);
        $this->assertSame($this->defaultLocale, $slug->locale);
    }

    public function test_seeder_skips_entities_without_slug(): void
    {
        // Products require a slug, but if slug is null (hypothetically empty), should be skipped
        // We use a product with an empty string slug
        \Illuminate\Support\Facades\DB::table('products')->insert([
            'name'      => json_encode(['en' => 'No Slug Product']),
            'slug'      => '',
            'is_active' => true,
        ]);

        $this->seed(SlugPopulationSeeder::class);

        $count = Slug::where('slug', '')->count();
        $this->assertSame(0, $count, 'Entities with empty slugs should not be inserted into slugs table');
    }

    public function test_seeder_populates_all_entity_types_together(): void
    {
        Product::create(['name' => ['en' => 'Product A'], 'slug' => 'product-a', 'is_active' => true]);
        Category::create(['name' => ['en' => 'Category A'], 'slug' => 'category-a', 'is_active' => true]);
        Manufacturer::create(['name' => 'Manufacturer A', 'slug' => 'manufacturer-a']);

        $this->seed(SlugPopulationSeeder::class);

        $this->assertSame(3, Slug::count(), 'Slugs table should contain one record per entity');
    }

    public function test_seeder_sets_correct_sluggable_id(): void
    {
        $product = Product::create(['name' => ['en' => 'ID Test'], 'slug' => 'id-test', 'is_active' => true]);

        $this->seed(SlugPopulationSeeder::class);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => $this->defaultLocale,
            'slug'           => 'id-test',
        ]);
    }
}
