<?php

namespace Tests\Feature;

use App\Domains\Manufacturer\Models\Manufacturer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;
use Tests\TestCase;

class ManufacturerModelTest extends TestCase
{
    use RefreshDatabase;

    // ── HasTranslations ───────────────────────────────────────────────────────

    public function test_manufacturer_uses_has_translations_trait(): void
    {
        $this->assertContains(
            HasTranslations::class,
            class_uses_recursive(Manufacturer::class),
        );
    }

    public function test_manufacturer_translatable_includes_required_fields(): void
    {
        $manufacturer = new Manufacturer();

        $expected = ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($expected as $field) {
            $this->assertContains($field, $manufacturer->getTranslatableAttributes());
        }
    }

    public function test_manufacturer_can_set_and_get_translation_for_name(): void
    {
        $manufacturer = Manufacturer::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $manufacturer->setTranslation('name', 'fr', 'Acme Société');
        $manufacturer->save();

        $this->assertSame('Acme Corp', $manufacturer->getTranslation('name', 'en'));
        $this->assertSame('Acme Société', $manufacturer->getTranslation('name', 'fr'));
    }

    public function test_manufacturer_can_set_and_get_translation_for_description(): void
    {
        $manufacturer = Manufacturer::create([
            'name'        => 'Test Manufacturer',
            'slug'        => 'test-manufacturer',
            'description' => 'English description',
        ]);

        $manufacturer->setTranslation('description', 'fr', 'Description française');
        $manufacturer->save();

        $this->assertSame('English description', $manufacturer->getTranslation('description', 'en'));
        $this->assertSame('Description française', $manufacturer->getTranslation('description', 'fr'));
    }

    public function test_manufacturer_can_set_and_get_translation_for_meta_fields(): void
    {
        $manufacturer = Manufacturer::create([
            'name'              => 'Meta Manufacturer',
            'slug'              => 'meta-manufacturer',
            'meta_title'        => 'SEO Title',
            'meta_description'  => 'SEO Description',
            'meta_keywords'     => 'keyword1, keyword2',
        ]);

        $manufacturer->setTranslation('meta_title', 'fr', 'Titre SEO');
        $manufacturer->save();

        $this->assertSame('SEO Title', $manufacturer->getTranslation('meta_title', 'en'));
        $this->assertSame('Titre SEO', $manufacturer->getTranslation('meta_title', 'fr'));
    }

    // ── HasMedia / InteractsWithMedia ─────────────────────────────────────────

    public function test_manufacturer_implements_has_media_interface(): void
    {
        $this->assertInstanceOf(HasMedia::class, new Manufacturer());
    }

    public function test_manufacturer_registers_thumbnail_media_collection(): void
    {
        $manufacturer = Manufacturer::create([
            'name' => 'Media Manufacturer',
            'slug' => 'media-manufacturer',
        ]);

        $collections = $manufacturer->getRegisteredMediaCollections();
        $names       = collect($collections)->pluck('name')->all();

        $this->assertContains('thumbnail', $names);
    }

    // ── $fillable ─────────────────────────────────────────────────────────────

    public function test_manufacturer_fillable_includes_new_meta_and_description_fields(): void
    {
        $manufacturer = new Manufacturer();

        $expected = ['description', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($expected as $field) {
            $this->assertContains($field, $manufacturer->getFillable());
        }
    }

    // ── Existing functionality unchanged ──────────────────────────────────────

    public function test_manufacturer_products_relationship_still_works(): void
    {
        $manufacturer = Manufacturer::create([
            'name' => 'Relationship Manufacturer',
            'slug' => 'relationship-manufacturer',
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $manufacturer->products(),
        );
    }
}
