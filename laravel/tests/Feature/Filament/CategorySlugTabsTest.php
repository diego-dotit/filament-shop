<?php

namespace Tests\Feature\Filament;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategorySlugTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Slug fields inside tabs ────────────────────────────────────────────

    public function test_create_form_has_slug_field_inside_language_tab(): void
    {
        // Uses the CategoryResource fallback (no languages in DB → 'en' tab)
        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('slug_en');
    }

    public function test_create_form_has_slug_field_for_each_configured_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    public function test_slug_field_is_inside_the_tabs_section_alongside_name(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Both name and slug fields must exist in the form (they live in the same tab schema)
        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('name.en')
            ->assertFormFieldExists('slug_en');
    }

    // ── Slug key format ────────────────────────────────────────────────────

    public function test_slug_fields_use_slug_underscore_code_key_format(): void
    {
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => true]);

        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('slug_fr');
    }

    // ── Form population from slugs table ──────────────────────────────────

    public function test_edit_form_populates_slug_from_slugs_table(): void
    {
        // HasSlugs auto-creates a slug record when a Category is saved with a Language in DB
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = Category::factory()->create([
            'name' => ['en' => 'Test Category'],
            'slug' => 'test-category',
        ]);

        // HasSlugs auto-created slug from name — verify it's populated in the form
        $expectedSlug = $category->getSlugForLocale('en')?->slug;
        $this->assertNotNull($expectedSlug, 'HasSlugs should auto-create slug for en locale');

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(['slug_en' => $expectedSlug]);
    }

    public function test_edit_form_populates_slug_for_multiple_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        $category = Category::factory()->create([
            'name' => ['en' => 'Bicycles', 'de' => 'Fahrraeder'],
            'slug' => 'bicycles',
        ]);

        // HasSlugs auto-creates slugs for each locale
        $slugEn = $category->getSlugForLocale('en')?->slug;
        $slugDe = $category->getSlugForLocale('de')?->slug;

        $this->assertNotNull($slugEn);
        $this->assertNotNull($slugDe);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet([
                'slug_en' => $slugEn,
                'slug_de' => $slugDe,
            ]);
    }

    public function test_edit_form_shows_null_slug_when_no_slug_record_exists(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        // Category with name only in 'en' — 'fr' slug won't be auto-created
        $category = Category::factory()->create([
            'name' => ['en' => 'English Only'],
            'slug' => 'english-only',
        ]);

        // No fr slug record — field should be null
        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(['slug_fr' => null]);
    }

    // ── Manual input ──────────────────────────────────────────────────────

    public function test_slug_field_allows_manual_text_input(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateCategory::class)
            ->fillForm(['slug_en' => 'my-custom-slug'])
            ->assertFormSet(['slug_en' => 'my-custom-slug']);
    }
}
