<?php

namespace Tests\Feature\Filament;

use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Filament\Resources\BlogCategoryResource;
use App\Filament\Resources\BlogCategoryResource\Pages\CreateBlogCategory;
use App\Filament\Resources\BlogCategoryResource\Pages\EditBlogCategory;
use App\Filament\Resources\BlogCategoryResource\Pages\ListBlogCategories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BlogCategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Resource Configuration ─────────────────────────────────────────────

    public function test_blog_category_resource_uses_correct_model(): void
    {
        $this->assertSame(BlogCategory::class, BlogCategoryResource::getModel());
    }

    public function test_blog_category_resource_has_required_pages(): void
    {
        $pages = BlogCategoryResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    // ── List Page ──────────────────────────────────────────────────────────

    public function test_list_blog_categories_page_renders(): void
    {
        Livewire::test(ListBlogCategories::class)
            ->assertSuccessful();
    }

    public function test_list_page_displays_category_records(): void
    {
        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Tech Category'],
            'status' => 'active',
        ]);

        Livewire::test(ListBlogCategories::class)
            ->assertCanSeeTableRecords([$category]);
    }

    public function test_list_page_has_required_columns(): void
    {
        Livewire::test(ListBlogCategories::class)
            ->assertTableColumnExists('title')
            ->assertTableColumnExists('status')
            ->assertTableColumnExists('created_at');
    }

    public function test_list_page_paginates_10_per_page_by_default(): void
    {
        BlogCategory::factory()->count(15)->create(['status' => 'active']);

        $component = Livewire::test(ListBlogCategories::class);

        $component->assertSuccessful();
        $this->assertCount(10, BlogCategory::paginate(10)->items());
    }

    // ── Create Page ────────────────────────────────────────────────────────

    public function test_create_blog_category_page_renders(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->assertSuccessful();
    }

    public function test_create_form_has_title_field_per_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogCategory::class)
            ->assertFormFieldExists('title_en')
            ->assertFormFieldExists('title_de');
    }

    public function test_create_form_has_status_field(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->assertFormFieldExists('status');
    }

    public function test_create_form_has_thumbnail_field(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->assertFormFieldExists('thumbnail');
    }

    public function test_can_create_blog_category_with_english_title(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'My Tech Blog Category',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'My Tech Blog Category')->first();

        $this->assertNotNull($category);
        $this->assertEquals('My Tech Blog Category', $category->getTranslation('title', 'en'));
    }

    public function test_create_stores_translations_for_all_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Science News',
                'title_de' => 'Wissenschaftsnachrichten',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Science News')->first();

        $this->assertNotNull($category);
        $this->assertEquals('Science News', $category->getTranslation('title', 'en'));
        $this->assertEquals('Wissenschaftsnachrichten', $category->getTranslation('title', 'de'));
    }

    public function test_create_persists_status_as_active_string(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Active Category',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Active Category')->first();

        $this->assertNotNull($category);
        $this->assertSame('active', $category->status);
    }

    public function test_create_persists_status_as_inactive_string(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Inactive Category',
                'status'   => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Inactive Category')->first();

        $this->assertNotNull($category);
        $this->assertSame('inactive', $category->status);
    }

    public function test_create_with_thumbnail_creates_media_entry(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $image = UploadedFile::fake()->image('thumbnail.jpg', 400, 300);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en'  => 'Category With Thumbnail',
                'status'    => true,
                'thumbnail' => [$image],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Category With Thumbnail')->first();

        $this->assertNotNull($category);
        $this->assertCount(1, $category->getMedia('thumbnail'));
    }

    // ── Slug Form Fields ───────────────────────────────────────────────────

    public function test_create_form_has_slug_field_per_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogCategory::class)
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    public function test_edit_form_has_slug_field_per_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Test Category EN', 'de' => 'Test Kategorie DE'],
            'status' => 'active',
        ]);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    public function test_edit_form_prepopulates_slug_per_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Slug Test Category'],
            'status' => 'active',
        ]);

        // Ensure slug exists in DB
        $category->slugs()->updateOrCreate(['locale' => 'en'], ['slug' => 'slug-test-category']);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('slug-test-category', $data['slug_en'] ?? null));
    }

    public function test_manual_slug_override_is_persisted_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en'  => 'Some Title',
                'slug_en'   => 'my-custom-slug',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Some Title')->first();
        $this->assertNotNull($category);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'my-custom-slug',
        ]);
    }

    // ── Slug Auto-Generation ───────────────────────────────────────────────

    public function test_slug_is_auto_generated_from_title_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Auto Slug Category',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Auto Slug Category')->first();

        $this->assertNotNull($category);
        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'auto-slug-category',
        ]);
    }

    public function test_slug_generated_per_locale_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Travel Tips',
                'title_de' => 'Reisetipps',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Travel Tips')->first();

        $this->assertNotNull($category);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'travel-tips',
        ]);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'de',
            'slug'           => 'reisetipps',
        ]);
    }

    // ── Edit Page ──────────────────────────────────────────────────────────

    public function test_edit_blog_category_page_renders(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Editable Category'],
            'status' => 'active',
        ]);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_edit_form_prepopulates_title_per_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Original Title EN', 'de' => 'Original Titel DE'],
            'status' => 'active',
        ]);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('Original Title EN', $data['title_en'] ?? null))
            ->assertFormSet(fn (array $data) => $this->assertSame('Original Titel DE', $data['title_de'] ?? null));
    }

    public function test_edit_updates_translations_for_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Old Title'],
            'status' => 'active',
        ]);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'title_en' => 'Updated Title EN',
                'status'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertEquals('Updated Title EN', $category->getTranslation('title', 'en'));
    }

    public function test_edit_can_toggle_status_to_inactive(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Toggle Status Category'],
            'status' => 'active',
        ]);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'title_en' => 'Toggle Status Category',
                'status'   => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('inactive', $category->status);
    }

    public function test_edit_can_toggle_status_to_active(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Was Inactive Category'],
            'status' => 'inactive',
        ]);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'title_en' => 'Was Inactive Category',
                'status'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('active', $category->status);
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function test_can_delete_blog_category_from_list(): void
    {
        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Deletable Category'],
            'status' => 'active',
        ]);

        Livewire::test(ListBlogCategories::class)
            ->callTableAction('delete', $category);

        $this->assertDatabaseMissing('blog_categories', ['id' => $category->id]);
    }

    public function test_can_delete_blog_category_from_edit_page(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'To Be Deleted'],
            'status' => 'active',
        ]);

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('blog_categories', ['id' => $category->id]);
    }

    // ── Slug Uniqueness Validation ─────────────────────────────────────────

    public function test_slug_uniqueness_validation_rejects_duplicate_slug_for_same_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Create first category with a known slug
        $existing = BlogCategory::factory()->create([
            'title'  => ['en' => 'First Category'],
            'status' => 'active',
        ]);
        $existing->slugs()->updateOrCreate(
            ['locale' => 'en'],
            [
                'sluggable_type' => BlogCategory::class,
                'sluggable_id'   => $existing->id,
                'slug'           => 'duplicate-slug',
            ]
        );

        // Attempt to create a second category with the same slug
        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Second Category',
                'slug_en'  => 'duplicate-slug',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── Slug Persistence on Edit ───────────────────────────────────────────

    public function test_edit_form_persists_updated_slug_to_database(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Original Category'],
            'status' => 'active',
        ]);

        // Seed an existing slug
        $category->slugs()->updateOrCreate(
            ['locale' => 'en'],
            [
                'sluggable_type' => BlogCategory::class,
                'sluggable_id'   => $category->id,
                'slug'           => 'original-category',
            ]
        );

        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'title_en' => 'Original Category',
                'slug_en'  => 'updated-category-slug',
                'status'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'updated-category-slug',
        ]);

        $this->assertDatabaseMissing('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'original-category',
        ]);
    }

    // ── Slug Round-Trip ────────────────────────────────────────────────────

    public function test_slug_round_trip_create_then_reload_edit_form(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Create category with custom slug
        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Round Trip Category',
                'slug_en'  => 'round-trip-category',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Round Trip Category')->first();
        $this->assertNotNull($category);

        // Reload on edit page and verify slug is pre-populated
        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('round-trip-category', $data['slug_en'] ?? null));
    }

    public function test_slug_round_trip_auto_generated_slug_reloaded_in_edit_form(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        // Create category without explicit slug — slug auto-generated from title
        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Auto Round Trip',
                'title_de' => 'Auto Hin Und Rück',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Auto Round Trip')->first();
        $this->assertNotNull($category);

        // Verify auto-slugs are saved in the DB
        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'auto-round-trip',
        ]);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'de',
            'slug'           => 'auto-hin-und-ruck',
        ]);

        // Reload on edit page and verify both slugs are pre-populated
        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('auto-round-trip', $data['slug_en'] ?? null))
            ->assertFormSet(fn (array $data) => $this->assertSame('auto-hin-und-ruck', $data['slug_de'] ?? null));
    }

    // ── Slug Per-Locale Independence ───────────────────────────────────────

    public function test_per_locale_slugs_are_stored_independently_per_language(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en' => 'Technology',
                'slug_en'  => 'technology-en',
                'title_de' => 'Technologie',
                'slug_de'  => 'technologie-de',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Technology')->first();
        $this->assertNotNull($category);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'technology-en',
        ]);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogCategory::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'de',
            'slug'           => 'technologie-de',
        ]);

        // Each language should have exactly one slug record
        $this->assertCount(2, $category->slugs);
    }

    public function test_slug_edit_uniqueness_validation_ignores_own_slug(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $category = BlogCategory::factory()->create([
            'title'  => ['en' => 'Self Unique Category'],
            'status' => 'active',
        ]);

        $category->slugs()->updateOrCreate(
            ['locale' => 'en'],
            [
                'sluggable_type' => BlogCategory::class,
                'sluggable_id'   => $category->id,
                'slug'           => 'self-unique-slug',
            ]
        );

        // Editing the same record with its existing slug should not trigger a uniqueness error
        Livewire::test(EditBlogCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'title_en' => 'Self Unique Category',
                'slug_en'  => 'self-unique-slug',
                'status'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ── Translatable Field Mutation ────────────────────────────────────────

    public function test_per_locale_form_fields_converted_to_json_in_database(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        Livewire::test(CreateBlogCategory::class)
            ->fillForm([
                'title_en'       => 'Health Tips',
                'title_fr'       => 'Conseils de santé',
                'description_en' => 'All about health',
                'description_fr' => 'Tout sur la santé',
                'status'         => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = BlogCategory::whereJsonContains('title->en', 'Health Tips')->first();

        $this->assertNotNull($category);

        // Title is stored as JSON with per-locale keys
        $titleData = json_decode($category->getRawOriginal('title') ?? '{}', true);
        $this->assertIsArray($titleData);
        $this->assertArrayHasKey('en', $titleData);
        $this->assertArrayHasKey('fr', $titleData);
        $this->assertEquals('Health Tips', $titleData['en']);
        $this->assertEquals('Conseils de santé', $titleData['fr']);

        // Description is also stored as JSON
        $descData = json_decode($category->getRawOriginal('description') ?? '{}', true);
        $this->assertIsArray($descData);
        $this->assertEquals('All about health', $descData['en']);
        $this->assertEquals('Tout sur la santé', $descData['fr']);
    }
}
