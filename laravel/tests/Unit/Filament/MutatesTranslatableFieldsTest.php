<?php

namespace Tests\Unit\Filament;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\Concerns\MutatesTranslatableFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for T2.2: MutatesTranslatableFields shared concern trait.
 *
 * Verifies that the trait correctly:
 *  - converts per-locale underscore fields (name_en, meta_title_fr, etc.) to JSON arrays
 *  - strips slug_{code} fields from persisted data
 *  - handles empty $translatableFields (slug-only mode, e.g. for CategoryResource)
 *  - persists pending slugs via the record's slugs() relation
 */
class MutatesTranslatableFieldsTest extends TestCase
{
    use RefreshDatabase;

    // ── buildTranslationData with translatable fields ─────────────────────

    /**
     * With $translatableFields = ['name', 'description'], per-locale flat fields
     * must be collected into JSON arrays keyed by locale code.
     */
    public function test_build_translation_data_converts_underscore_fields_to_json_arrays(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $trait = $this->makeTraitInstance(['name', 'description']);

        $data = [
            'name_en'        => 'Widget',
            'name_fr'        => 'Gadget',
            'description_en' => 'A widget',
            'description_fr' => 'Un gadget',
            'is_active'      => true,
        ];

        $result = $trait->exposeFormData($data);

        $this->assertIsArray($result['name']);
        $this->assertSame('Widget', $result['name']['en']);
        $this->assertSame('Gadget', $result['name']['fr']);

        $this->assertIsArray($result['description']);
        $this->assertSame('A widget', $result['description']['en']);
        $this->assertSame('Un gadget', $result['description']['fr']);
    }

    /**
     * Per-locale flat fields must be removed from the returned array so they
     * are not passed as unknown columns to Eloquent.
     */
    public function test_build_translation_data_removes_flat_locale_fields(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'is_default' => false]);

        $trait = $this->makeTraitInstance(['name', 'meta_title']);

        $data = [
            'name_en'       => 'Sprocket',
            'name_de'       => 'Ritzel',
            'meta_title_en' => 'Sprocket SEO',
            'meta_title_de' => 'Ritzel SEO',
        ];

        $result = $trait->exposeFormData($data);

        $this->assertArrayNotHasKey('name_en', $result);
        $this->assertArrayNotHasKey('name_de', $result);
        $this->assertArrayNotHasKey('meta_title_en', $result);
        $this->assertArrayNotHasKey('meta_title_de', $result);
    }

    /**
     * Empty or null per-locale values must NOT be included in the JSON array
     * so that Spatie HasTranslations does not store empty strings.
     */
    public function test_build_translation_data_excludes_empty_values(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $trait = $this->makeTraitInstance(['name', 'description']);

        $data = [
            'name_en'        => 'Only English',
            'name_fr'        => '',
            'description_en' => null,
            'description_fr' => '',
        ];

        $result = $trait->exposeFormData($data);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('fr', $result['name']);

        $this->assertArrayHasKey('description', $result);
        $this->assertEmpty($result['description']);
    }

    /**
     * Trait must handle all five SEO fields simultaneously: name, description,
     * meta_title, meta_description, meta_keywords.
     */
    public function test_build_translation_data_handles_all_five_meta_fields(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $trait = $this->makeTraitInstance(['name', 'description', 'meta_title', 'meta_description', 'meta_keywords']);

        $data = [
            'name_en'             => 'Product',
            'description_en'      => 'Desc',
            'meta_title_en'       => 'Meta Title',
            'meta_description_en' => 'Meta Desc',
            'meta_keywords_en'    => 'kw1, kw2',
        ];

        $result = $trait->exposeFormData($data);

        $this->assertSame('Product', $result['name']['en']);
        $this->assertSame('Desc', $result['description']['en']);
        $this->assertSame('Meta Title', $result['meta_title']['en']);
        $this->assertSame('Meta Desc', $result['meta_description']['en']);
        $this->assertSame('kw1, kw2', $result['meta_keywords']['en']);
    }

    // ── slug extraction ───────────────────────────────────────────────────

    /**
     * slug_{code} fields must always be stripped from form data, regardless
     * of $translatableFields setting.
     */
    public function test_build_translation_data_always_strips_slug_fields(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        // Even with empty $translatableFields, slugs must be stripped
        $trait = $this->makeTraitInstance([]);

        $data = [
            'slug_en' => 'my-category',
            'slug_fr' => 'ma-categorie',
            'name'    => ['en' => 'My Category', 'fr' => 'Ma Catégorie'],
        ];

        $result = $trait->exposeFormData($data);

        $this->assertArrayNotHasKey('slug_en', $result);
        $this->assertArrayNotHasKey('slug_fr', $result);
        // Non-slug fields must be preserved
        $this->assertArrayHasKey('name', $result);
    }

    /**
     * When $translatableFields is empty, non-slug fields pass through unchanged
     * (dot-notation Spatie fields from CategoryResource form are already correct).
     */
    public function test_build_translation_data_with_empty_fields_preserves_existing_data(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $trait = $this->makeTraitInstance([]);

        $data = [
            'name'      => ['en' => 'My Category'],
            'is_active' => true,
            'slug_en'   => 'my-category',
        ];

        $result = $trait->exposeFormData($data);

        $this->assertSame(['en' => 'My Category'], $result['name']);
        $this->assertTrue($result['is_active']);
        $this->assertArrayNotHasKey('slug_en', $result);
    }

    // ── persistPendingSlugs ───────────────────────────────────────────────

    /**
     * persistPendingSlugs must call updateOrCreate on the record's slugs()
     * relation for each collected locale slug.
     */
    public function test_persist_pending_slugs_calls_update_or_create_for_each_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $tracker = new \stdClass;
        $tracker->calls = [];

        $record = $this->makeRecordStub($tracker);

        $trait = $this->makeTraitInstance(['name']);

        // Prime pendingSlugs by running buildTranslationData
        $data = [
            'name_en' => 'Widget',
            'name_fr' => 'Gadget',
            'slug_en' => 'widget',
            'slug_fr' => 'gadget',
        ];
        $trait->exposeFormData($data);

        $trait->record = $record;
        $trait->exposePersistSlugs();

        $this->assertCount(2, $tracker->calls, 'updateOrCreate must be called once per locale slug');
        $locales = array_column(array_column($tracker->calls, 'match'), 'locale');
        $this->assertContains('en', $locales);
        $this->assertContains('fr', $locales);
    }

    /**
     * After persistPendingSlugs runs, pendingSlugs must be cleared so a
     * subsequent call does not attempt to re-persist stale slugs.
     */
    public function test_persist_pending_slugs_clears_pending_slugs_after_run(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $tracker = new \stdClass;
        $tracker->calls = [];

        $record = $this->makeRecordStub($tracker);

        $trait = $this->makeTraitInstance(['name']);

        $data = ['name_en' => 'Widget', 'slug_en' => 'widget'];
        $trait->exposeFormData($data);

        $trait->record = $record;
        $trait->exposePersistSlugs(); // first call: 1 slug
        $this->assertCount(1, $tracker->calls);

        $trait->exposePersistSlugs(); // second call: pendingSlugs cleared → 0 new calls
        $this->assertCount(1, $tracker->calls, 'Second persistPendingSlugs must not re-persist cleared slugs');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Build an anonymous class that uses the trait and exposes its protected
     * methods for direct testing. The $translatableFields property is injected
     * at construction time.
     *
     * @param  list<string>  $fields
     */
    private function makeTraitInstance(array $fields): object
    {
        return new class ($fields) {
            use MutatesTranslatableFields;

            /** Exposed so tests can inject a fake record. */
            public mixed $record = null;

            public function __construct(array $translatableFields)
            {
                $this->translatableFields = $translatableFields;
            }

            public function exposeFormData(array $data): array
            {
                return $this->buildTranslationData($data);
            }

            public function exposePersistSlugs(): void
            {
                $this->persistPendingSlugs();
            }
        };
    }

    /**
     * Build a plain-object record stub whose slugs() returns a relation stub
     * that records updateOrCreate calls on the given $tracker->calls array.
     */
    private function makeRecordStub(\stdClass $tracker): object
    {
        return new class ($tracker) {
            private \stdClass $tracker;

            public function __construct(\stdClass $tracker)
            {
                $this->tracker = $tracker;
            }

            public function slugs(): object
            {
                return new class ($this->tracker) {
                    private \stdClass $tracker;

                    public function __construct(\stdClass $tracker)
                    {
                        $this->tracker = $tracker;
                    }

                    public function updateOrCreate(array $match, array $values): \stdClass
                    {
                        $this->tracker->calls[] = ['match' => $match, 'values' => $values];

                        return new \stdClass;
                    }
                };
            }
        };
    }
}
