<?php

namespace Tests\Feature;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Shared\Traits\HasSlugs;
use App\Domains\Slug\Models\Slug;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHasSlugsTest extends TestCase
{
    use RefreshDatabase;

    // ── Trait usage ───────────────────────────────────────────────────────────

    public function test_category_model_uses_has_slugs_trait(): void
    {
        $traits = class_uses_recursive(Category::class);

        $this->assertContains(HasSlugs::class, $traits);
    }

    // ── Relationship ──────────────────────────────────────────────────────────

    public function test_slugs_relationship_returns_morph_many(): void
    {
        $category = Category::factory()->create();

        $this->assertInstanceOf(MorphMany::class, $category->slugs());
    }

    // ── Auto-generation on create ─────────────────────────────────────────────

    public function test_saving_new_category_auto_generates_slug_for_active_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = Category::create([
            'name'      => ['en' => 'Electronics'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slug = $category->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('electronics', $slug->slug);
    }

    public function test_saving_new_category_generates_slugs_for_all_active_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $category = Category::create([
            'name'      => ['en' => 'Clothing', 'fr' => 'Vêtements'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slugEn = $category->getSlugForLocale('en');
        $slugFr = $category->getSlugForLocale('fr');

        $this->assertNotNull($slugEn);
        $this->assertSame('clothing', $slugEn->slug);

        $this->assertNotNull($slugFr);
        $this->assertSame('vetements', $slugFr->slug);
    }

    // ── Update behaviour ──────────────────────────────────────────────────────

    public function test_editing_category_name_updates_existing_slug(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = Category::create([
            'name'      => ['en' => 'Old Name'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        // Slug was auto-created on save; confirm it exists
        $this->assertSame('old-name', $category->getSlugForLocale('en')->slug);

        // Now update name — existing slug entry must be updated to match new name
        $category->update(['name' => ['en' => 'New Name']]);

        $slug = $category->fresh()->getSlugForLocale('en');
        $this->assertSame('new-name', $slug->slug);
    }

    // ── Empty translation skipped ─────────────────────────────────────────────

    public function test_slug_not_generated_for_locale_with_no_name_translation(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'is_default' => false]);

        $category = Category::create([
            'name'      => ['en' => 'Books'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $this->assertNotNull($category->getSlugForLocale('en'));
        $this->assertNull($category->getSlugForLocale('de'));
    }
}
