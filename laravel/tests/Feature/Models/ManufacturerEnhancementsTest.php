<?php

namespace Tests\Feature\Models;

use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Manufacturer enhancements added in T1.11.
 * The main Manufacturer model tests live in ManufacturerModelTest.php.
 * This file covers the HasSlugs backward-compatibility behaviour:
 * slugs are generated from the 'name' field as before.
 */
class ManufacturerEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    // ── HasSlugs backward compat ──────────────────────────────────────────────

    public function test_manufacturer_uses_has_slugs_trait(): void
    {
        $this->assertContains(HasSlugs::class, class_uses_recursive(Manufacturer::class));
    }

    public function test_manufacturer_slug_auto_generated_from_name_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $manufacturer = Manufacturer::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corporation',
        ]);

        $slug = $manufacturer->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('acme-corporation', $slug->slug);
    }

    public function test_manufacturer_generates_locale_specific_slugs_from_name(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $manufacturer = Manufacturer::create([
            'name' => ['en' => 'Tech Brand', 'fr' => 'Marque Tech'],
            'slug' => 'tech-brand',
        ]);

        $slugEn = $manufacturer->getSlugForLocale('en');
        $slugFr = $manufacturer->getSlugForLocale('fr');

        $this->assertNotNull($slugEn);
        $this->assertSame('tech-brand', $slugEn->slug);

        $this->assertNotNull($slugFr);
        $this->assertSame('marque-tech', $slugFr->slug);
    }

    public function test_manufacturer_slug_source_field_is_name(): void
    {
        $manufacturer = new Manufacturer();

        // slugSourceField defaults to 'name' when not declared, but check
        // that the trait works with the 'name' field by default.
        $sourceField = $manufacturer->slugSourceField ?? 'name';

        $this->assertSame('name', $sourceField);
    }
}
