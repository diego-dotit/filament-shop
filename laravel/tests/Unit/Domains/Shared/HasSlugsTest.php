<?php

namespace Tests\Unit\Domains\Shared;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Product\Models\Product;
use App\Domains\Shared\Traits\HasSlugs;
use App\Domains\Slug\Models\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasSlugsTest extends TestCase
{
    use RefreshDatabase;

    // ── Relationship ──────────────────────────────────────────────────────────

    public function test_slugs_returns_morph_many_relation(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(MorphMany::class, $product->slugs());
    }

    // ── getSlugForLocale ──────────────────────────────────────────────────────

    public function test_get_slug_for_locale_returns_slug_for_matching_locale(): void
    {
        $product = Product::factory()->create();

        Slug::create([
            'sluggable_type' => Product::class,
            'sluggable_id'   => $product->id,
            'locale'         => 'en',
            'slug'           => 'my-product',
        ]);

        $slug = $product->getSlugForLocale('en');

        $this->assertInstanceOf(Slug::class, $slug);
        $this->assertSame('my-product', $slug->slug);
        $this->assertSame('en', $slug->locale);
    }

    public function test_get_slug_for_locale_returns_null_when_no_slug_for_locale(): void
    {
        $product = Product::factory()->create();

        $slug = $product->getSlugForLocale('fr');

        $this->assertNull($slug);
    }

    // ── Auto-generation (translatable models) ────────────────────────────────

    public function test_saving_auto_generates_slug_from_translated_name(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $product = Product::create([
            'name'      => ['en' => 'My Awesome Product'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slug = $product->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('my-awesome-product', $slug->slug);
    }

    public function test_saving_auto_generates_slugs_for_all_supported_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $product = Product::create([
            'name'      => ['en' => 'Bicycle', 'fr' => 'Vélo'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slugEn = $product->getSlugForLocale('en');
        $slugFr = $product->getSlugForLocale('fr');

        $this->assertNotNull($slugEn);
        $this->assertSame('bicycle', $slugEn->slug);

        $this->assertNotNull($slugFr);
        $this->assertSame('velo', $slugFr->slug);
    }

    public function test_saving_does_not_overwrite_existing_slug_when_name_unchanged(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $product = Product::create([
            'name'      => ['en' => 'First Name'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        // Manually update the slug to a custom one
        $existingSlug = $product->getSlugForLocale('en');
        $existingSlug->update(['slug' => 'custom-slug']);

        // Save the model again (name unchanged)
        $product->save();

        $slug = $product->fresh()->getSlugForLocale('en');
        $this->assertSame('custom-slug', $slug->slug);
    }

    public function test_saving_updates_existing_slug_when_name_changes(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $product = Product::create([
            'name'      => ['en' => 'Old Name'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $this->assertSame('old-name', $product->getSlugForLocale('en')->slug);

        // Update the name — the existing slug entry must be updated
        $product->update(['name' => ['en' => 'New Name']]);

        $slug = $product->fresh()->getSlugForLocale('en');
        $this->assertSame('new-name', $slug->slug);
    }

    public function test_saving_skips_slug_generation_when_name_translation_is_empty(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        // Only 'en' translation provided, 'fr' name is empty
        $product = Product::create([
            'name'      => ['en' => 'Only English'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slugEn = $product->getSlugForLocale('en');
        $slugFr = $product->getSlugForLocale('fr');

        $this->assertNotNull($slugEn);
        $this->assertNull($slugFr);
    }

    // ── Non-translatable model (Manufacturer) ─────────────────────────────────

    public function test_non_translatable_model_gets_slug_from_plain_name(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $manufacturer = Manufacturer::create(['name' => 'Apple Inc', 'slug' => 'apple-inc']);

        $slugEn = $manufacturer->getSlugForLocale('en');

        $this->assertNotNull($slugEn);
        $this->assertSame('apple-inc', $slugEn->slug);
    }

    public function test_non_translatable_model_generates_same_slug_for_all_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        // For non-translatable models, both locales produce the same slug ('samsung'),
        // but the global unique constraint on slugs.slug means only the first locale
        // can be inserted. The trait must NOT throw; it skips duplicate slugs.
        $manufacturer = Manufacturer::create(['name' => 'Samsung', 'slug' => 'samsung']);

        // en locale should always get a slug
        $slugEn = $manufacturer->getSlugForLocale('en');
        $this->assertNotNull($slugEn);
        $this->assertSame('samsung', $slugEn->slug);

        // fr locale is skipped silently because 'samsung' is already taken globally
        $slugFr = $manufacturer->getSlugForLocale('fr');
        $this->assertNull($slugFr);
    }

    // ── Trait usage ───────────────────────────────────────────────────────────

    public function test_product_model_uses_has_slugs_trait(): void
    {
        $traits = class_uses_recursive(Product::class);

        $this->assertContains(HasSlugs::class, $traits);
    }

    public function test_manufacturer_model_uses_has_slugs_trait(): void
    {
        $traits = class_uses_recursive(Manufacturer::class);

        $this->assertContains(HasSlugs::class, $traits);
    }
}
