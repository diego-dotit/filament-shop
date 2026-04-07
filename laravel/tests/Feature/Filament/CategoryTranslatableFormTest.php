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

class CategoryTranslatableFormTest extends TestCase
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

    public function test_create_form_has_description_field_per_language(): void
    {
        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('description.en')
            ->assertFormFieldExists('description.de');
    }

    public function test_create_form_has_meta_title_field_per_language(): void
    {
        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('meta_title.en')
            ->assertFormFieldExists('meta_title.de');
    }

    public function test_create_form_has_meta_description_field_per_language(): void
    {
        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('meta_description.en')
            ->assertFormFieldExists('meta_description.de');
    }

    public function test_create_form_has_meta_keywords_field_per_language(): void
    {
        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('meta_keywords.en')
            ->assertFormFieldExists('meta_keywords.de');
    }

    // ── HTML Description Persistence ───────────────────────────────────────

    public function test_creating_category_with_html_description_saves_html_as_is(): void
    {
        $html = '<p>This is <strong>bold</strong> and <em>italic</em> text.</p>';

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'        => ['en' => 'Category with HTML'],
                'slug_en'     => 'category-with-html',
                'description' => ['en' => $html],
                'is_active'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'category-with-html')->first();
        $this->assertNotNull($category);
        $this->assertSame($html, $category->getTranslation('description', 'en'));
    }

    // ── Translatable Meta Fields ───────────────────────────────────────────

    public function test_creating_category_saves_meta_fields_per_locale(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'             => ['en' => 'SEO Category'],
                'slug_en'          => 'seo-category',
                'meta_title'       => ['en' => 'EN Meta Title', 'de' => 'DE Meta Titel'],
                'meta_description' => ['en' => 'EN Meta Desc',  'de' => 'DE Meta Beschr'],
                'meta_keywords'    => ['en' => 'en, keywords',  'de' => 'de, stichwörter'],
                'is_active'        => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'seo-category')->first();
        $this->assertNotNull($category);

        $this->assertSame('EN Meta Title', $category->getTranslation('meta_title', 'en'));
        $this->assertSame('DE Meta Titel', $category->getTranslation('meta_title', 'de'));
        $this->assertSame('EN Meta Desc',  $category->getTranslation('meta_description', 'en'));
        $this->assertSame('DE Meta Beschr', $category->getTranslation('meta_description', 'de'));
        $this->assertSame('en, keywords',    $category->getTranslation('meta_keywords', 'en'));
        $this->assertSame('de, stichwörter', $category->getTranslation('meta_keywords', 'de'));
    }

    // ── Edit Form Pre-population ───────────────────────────────────────────

    public function test_edit_form_pre_populates_description_and_meta_fields(): void
    {
        $html = '<p>Existing <strong>HTML</strong> description.</p>';

        $category = Category::factory()->active()->create([
            'name'             => ['en' => 'Pre-populated Category'],
            'slug'             => 'pre-populated-category',
            'description'      => ['en' => $html, 'de' => '<p>Deutsche Beschreibung</p>'],
            'meta_title'       => ['en' => 'EN Title',  'de' => 'DE Titel'],
            'meta_description' => ['en' => 'EN Desc',   'de' => 'DE Beschr'],
            'meta_keywords'    => ['en' => 'en, kw',    'de' => 'de, sw'],
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'description'      => ['en' => $html,                      'de' => '<p>Deutsche Beschreibung</p>'],
                'meta_title'       => ['en' => 'EN Title',                 'de' => 'DE Titel'],
                'meta_description' => ['en' => 'EN Desc',                  'de' => 'DE Beschr'],
                'meta_keywords'    => ['en' => 'en, kw',                   'de' => 'de, sw'],
            ]);
    }

    // ── Translation Persistence across Save Cycles ─────────────────────────

    public function test_translations_preserved_across_save_cycles(): void
    {
        $category = Category::factory()->active()->create([
            'name'        => ['en' => 'Original Name EN', 'de' => 'Ursprünglicher Name DE'],
            'slug'        => 'original-translatable',
            'description' => ['en' => '<p>Original EN</p>', 'de' => '<p>Original DE</p>'],
            'meta_title'  => ['en' => 'Original Meta EN',   'de' => 'Original Meta DE'],
        ]);

        // First save: update EN only
        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name'        => ['en' => 'Updated Name EN', 'de' => 'Ursprünglicher Name DE'],
                'description' => ['en' => '<p>Updated EN</p>', 'de' => '<p>Original DE</p>'],
                'meta_title'  => ['en' => 'Updated Meta EN',   'de' => 'Original Meta DE'],
                'is_active'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('Updated Name EN',   $category->getTranslation('name', 'en'));
        $this->assertSame('Ursprünglicher Name DE', $category->getTranslation('name', 'de'));
        $this->assertSame('<p>Updated EN</p>', $category->getTranslation('description', 'en'));
        $this->assertSame('<p>Original DE</p>', $category->getTranslation('description', 'de'));
        $this->assertSame('Updated Meta EN',   $category->getTranslation('meta_title', 'en'));
        $this->assertSame('Original Meta DE',  $category->getTranslation('meta_title', 'de'));
    }
}
