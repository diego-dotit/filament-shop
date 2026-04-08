<?php

namespace Tests\Feature\Filament;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Filament\Resources\BlogArticleResource;
use App\Filament\Resources\BlogArticleResource\Pages\CreateBlogArticle;
use App\Filament\Resources\BlogArticleResource\Pages\EditBlogArticle;
use App\Filament\Resources\BlogArticleResource\Pages\ListBlogArticles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BlogArticleResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Language $defaultLanguage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        $this->defaultLanguage = Language::factory()->default()->create([
            'code' => 'en',
            'name' => 'English',
        ]);
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    private function makeArticle(array $overrides = []): BlogArticle
    {
        return BlogArticle::create(array_merge([
            'title'     => ['en' => 'Test Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now()->subDay(),
        ], $overrides));
    }

    private function makeCategory(array $overrides = []): BlogCategory
    {
        return BlogCategory::create(array_merge([
            'title'  => ['en' => 'Test Category'],
            'status' => 'active',
        ], $overrides));
    }

    // ── Resource definition ────────────────────────────────────────────────

    public function test_blog_article_resource_has_correct_model(): void
    {
        $this->assertEquals(BlogArticle::class, BlogArticleResource::getModel());
    }

    public function test_blog_article_resource_has_index_create_and_edit_pages(): void
    {
        $pages = BlogArticleResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    // ── List page ──────────────────────────────────────────────────────────

    public function test_list_blog_articles_page_renders_successfully(): void
    {
        Livewire::test(ListBlogArticles::class)
            ->assertSuccessful();
    }

    public function test_list_blog_articles_displays_article_records(): void
    {
        $articles = collect([
            $this->makeArticle(['title' => ['en' => 'Article One']]),
            $this->makeArticle(['title' => ['en' => 'Article Two']]),
            $this->makeArticle(['title' => ['en' => 'Article Three']]),
        ]);

        Livewire::test(ListBlogArticles::class)
            ->assertCanSeeTableRecords($articles);
    }

    public function test_list_blog_articles_has_required_table_columns(): void
    {
        Livewire::test(ListBlogArticles::class)
            ->assertTableColumnExists('id')
            ->assertTableColumnExists('author')
            ->assertTableColumnExists('post_date')
            ->assertTableColumnExists('status')
            ->assertTableColumnExists('created_at');
    }

    public function test_list_blog_articles_status_column_is_badge(): void
    {
        $article = $this->makeArticle(['status' => 'active']);

        Livewire::test(ListBlogArticles::class)
            ->assertTableColumnStateSet('status', 'active', record: $article);
    }

    // ── Create page ────────────────────────────────────────────────────────

    public function test_create_blog_article_page_renders_successfully(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->assertSuccessful();
    }

    public function test_create_form_has_translatable_fields_for_default_language(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->assertFormFieldExists('title_en')
            ->assertFormFieldExists('description_en')
            ->assertFormFieldExists('meta_title_en')
            ->assertFormFieldExists('meta_description_en')
            ->assertFormFieldExists('meta_keywords_en');
    }

    public function test_create_form_has_slug_field_for_default_language(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->assertFormFieldExists('slug_en');
    }

    public function test_create_form_has_slug_field_for_each_language(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogArticle::class)
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    public function test_create_article_saves_translatable_fields_as_json(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'       => 'My Article Title',
                'description_en' => 'My Article Description',
                'author'         => 'Jane Doe',
                'post_date'      => '2024-01-15',
                'status'         => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Jane Doe')->first();
        $this->assertNotNull($article);
        $this->assertSame('My Article Title', $article->getTranslation('title', 'en'));
        $this->assertSame('My Article Description', $article->getTranslation('description', 'en'));
    }

    public function test_create_article_persists_author_post_date_and_status(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Status Test Article',
                'author'    => 'John Author',
                'post_date' => '2024-06-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'John Author')->first();
        $this->assertNotNull($article);
        $this->assertSame('John Author', $article->author);
        $this->assertSame('2024-06-01', $article->post_date->format('Y-m-d'));
        $this->assertSame('active', $article->status);
    }

    public function test_create_article_with_status_false_stores_inactive(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Inactive Article',
                'author'    => 'Inactive Author',
                'post_date' => '2024-01-01',
                'status'    => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Inactive Author')->first();
        $this->assertNotNull($article);
        $this->assertSame('inactive', $article->status);
    }

    public function test_create_article_attaches_categories_via_pivot_table(): void
    {
        $cat1 = $this->makeCategory(['title' => ['en' => 'Technology']]);
        $cat2 = $this->makeCategory(['title' => ['en' => 'Science']]);

        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'       => 'Category Article',
                'author'         => 'Category Author',
                'post_date'      => '2024-01-01',
                'status'         => true,
                'blogCategories' => [$cat1->id, $cat2->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Category Author')->first();
        $this->assertNotNull($article);

        $this->assertDatabaseHas('blog_article_blog_category', [
            'blog_article_id'  => $article->id,
            'blog_category_id' => $cat1->id,
        ]);
        $this->assertDatabaseHas('blog_article_blog_category', [
            'blog_article_id'  => $article->id,
            'blog_category_id' => $cat2->id,
        ]);
    }

    public function test_create_article_with_thumbnail_uploads_to_thumbnail_collection(): void
    {
        $image = UploadedFile::fake()->image('thumbnail.jpg', 400, 300);

        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Media Article',
                'author'    => 'Media Author',
                'post_date' => '2024-01-01',
                'status'    => true,
                'thumbnail' => [$image],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Media Author')->first();
        $this->assertNotNull($article);
        $this->assertCount(1, $article->getMedia('thumbnail'));
    }

    public function test_create_article_requires_title_in_default_language(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en' => '',
                'author'   => 'No Title Author',
                'status'   => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['title_en']);
    }

    public function test_create_article_with_null_author_succeeds(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en' => 'Article Without Author',
                'author'   => null,
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('title->en', 'Article Without Author')->first();
        $this->assertNotNull($article);
        $this->assertNull($article->author);
    }

    public function test_author_column_is_nullable_in_database(): void
    {
        $article = BlogArticle::create([
            'title'  => ['en' => 'No Author Article'],
            'status' => 'active',
            'author' => null,
        ]);

        $this->assertNotNull($article->id);
        $this->assertNull($article->author);
        $this->assertDatabaseHas('blog_articles', [
            'id'     => $article->id,
            'author' => null,
        ]);
    }

    public function test_create_form_author_field_accepts_string_value(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'String Author Article',
                'author'    => 'Alice Smith',
                'post_date' => '2024-05-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Alice Smith')->first();
        $this->assertNotNull($article);
        $this->assertSame('Alice Smith', $article->author);
        $this->assertDatabaseHas('blog_articles', [
            'id'     => $article->id,
            'author' => 'Alice Smith',
        ]);
    }

    public function test_author_field_is_optional_in_form(): void
    {
        // Author field must not have a required() modifier — submitting without
        // an author should produce no form errors.
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en' => 'Optional Author Article',
                'author'   => null,
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors(['author']);
    }

    public function test_edit_article_can_clear_author_to_null(): void
    {
        $article = $this->makeArticle(['author' => 'Existing Author']);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'title_en' => $article->getTranslation('title', 'en'),
                'author'   => null,
                'status'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();
        $this->assertNull($article->author);
        $this->assertDatabaseHas('blog_articles', [
            'id'     => $article->id,
            'author' => null,
        ]);
    }

    public function test_round_trip_null_author_shows_empty_in_edit_form(): void
    {
        // Create article without author, reload edit form, verify author is null.
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en' => 'Round Trip No Author',
                'author'   => null,
                'status'   => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('title->en', 'Round Trip No Author')->first();
        $this->assertNotNull($article);
        $this->assertNull($article->author);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet([
                'author' => null,
            ]);
    }

    // ── Slug auto-generation ───────────────────────────────────────────────

    /**
     * Verifies model-level slug persistence via the HasSlugs model event (fired on `saved`).
     * When no explicit slug is provided, the slug is automatically generated from the title
     * by the HasSlugs trait, independently of the Filament form.
     *
     * NOTE: This does NOT test the form's `->live(onBlur: true)->afterStateUpdated()` reactive
     * slug pre-population (the UX preview that fills the slug field while the user types).
     * That client-side reactive behavior requires a browser/Dusk test to verify.
     */
    public function test_create_article_auto_generates_slug_from_title(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Auto Generated Slug Article',
                'author'    => 'Slug Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Slug Author')->first();
        $this->assertNotNull($article);

        $slug = $article->getSlugForLocale('en');
        $this->assertNotNull($slug);
        $this->assertSame('auto-generated-slug-article', $slug->slug);
    }

    public function test_create_article_persists_slug_to_slugs_table(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Slug Persistence Article',
                'author'    => 'Persist Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Persist Author')->first();
        $this->assertNotNull($article);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogArticle::class,
            'sluggable_id'   => $article->id,
            'locale'         => 'en',
        ]);
    }

    public function test_create_article_persists_explicit_slug_field_to_database(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Explicit Slug Field Article',
                'slug_en'   => 'my-custom-slug',
                'author'    => 'Explicit Slug Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Explicit Slug Author')->first();
        $this->assertNotNull($article);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogArticle::class,
            'sluggable_id'   => $article->id,
            'locale'         => 'en',
            'slug'           => 'my-custom-slug',
        ]);
    }

    /**
     * Verifies that model-level slug auto-generation (via HasSlugs) works independently per
     * locale — each locale's title produces its own slug record in the `slugs` table.
     *
     * NOTE: This does NOT test the form's `->live(onBlur: true)->afterStateUpdated()` reactive
     * slug pre-population (the UX preview that fills the slug field while the user types).
     * That client-side reactive behavior requires a browser/Dusk test to verify.
     */
    public function test_slug_auto_generation_is_independent_per_locale(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'English Article Title',
                'title_de'  => 'Deutscher Artikel Titel',
                'author'    => 'Multilang Slug Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Multilang Slug Author')->first();
        $this->assertNotNull($article);

        $slugEn = $article->getSlugForLocale('en');
        $slugDe = $article->getSlugForLocale('de');

        $this->assertNotNull($slugEn);
        $this->assertNotNull($slugDe);
        $this->assertSame('english-article-title', $slugEn->slug);
        $this->assertSame('deutscher-artikel-titel', $slugDe->slug);
    }

    public function test_manual_slug_override_is_not_overwritten_by_title(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Auto Generated Title Here',
                'slug_en'   => 'my-handcrafted-slug',
                'author'    => 'Manual Override Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Manual Override Author')->first();
        $this->assertNotNull($article);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogArticle::class,
            'sluggable_id'   => $article->id,
            'locale'         => 'en',
            'slug'           => 'my-handcrafted-slug',
        ]);
    }

    public function test_duplicate_slug_in_same_locale_shows_validation_error(): void
    {
        // Create a first article — HasSlugs auto-generates 'first-article' for 'en'
        $existing = $this->makeArticle(['title' => ['en' => 'First Article']]);

        // Overwrite the auto-generated slug with 'duplicate-slug' for locale 'en'
        $existing->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'duplicate-slug'],
        );

        // Attempt to create a second article with the same slug value
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Second Article',
                'slug_en'   => 'duplicate-slug',
                'author'    => 'Duplicate Slug Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_slug_round_trip_create_then_reload_edit_form(): void
    {
        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'Round Trip Article',
                'slug_en'   => 'round-trip-slug',
                'author'    => 'Round Trip Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Round Trip Author')->first();
        $this->assertNotNull($article);

        // Reload via edit form and verify slug is pre-populated from DB
        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'round-trip-slug',
            ]);
    }

    // ── Edit page ──────────────────────────────────────────────────────────

    public function test_edit_blog_article_page_renders_successfully(): void
    {
        $article = $this->makeArticle();

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_edit_form_has_slug_field_for_default_language(): void
    {
        $article = $this->makeArticle();

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormFieldExists('slug_en');
    }

    public function test_edit_form_has_slug_field_for_each_language(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        $article = $this->makeArticle();

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    public function test_edit_form_pre_populates_translatable_fields(): void
    {
        $article = $this->makeArticle([
            'title'       => ['en' => 'Pre-populated Title'],
            'description' => ['en' => 'Pre-populated Description'],
        ]);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet([
                'title_en'       => 'Pre-populated Title',
                'description_en' => 'Pre-populated Description',
            ]);
    }

    public function test_edit_form_pre_populates_author_and_post_date(): void
    {
        $article = $this->makeArticle([
            'author'    => 'Original Author',
            'post_date' => '2024-03-10',
        ]);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet([
                'author' => 'Original Author',
            ]);
    }

    public function test_edit_article_updates_translatable_fields(): void
    {
        $article = $this->makeArticle([
            'title'  => ['en' => 'Old Title'],
            'author' => 'Original Author',
        ]);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'title_en' => 'Updated Title',
                'author'   => 'Original Author',
                'status'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();
        $this->assertSame('Updated Title', $article->getTranslation('title', 'en'));
    }

    public function test_edit_article_updates_author_and_post_date(): void
    {
        $article = $this->makeArticle([
            'author'    => 'Old Author',
            'post_date' => '2024-01-01',
        ]);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'title_en'  => 'Updated Article',
                'author'    => 'New Author',
                'post_date' => '2024-12-31',
                'status'    => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();
        $this->assertSame('New Author', $article->author);
        $this->assertSame('2024-12-31', $article->post_date->format('Y-m-d'));
    }

    public function test_edit_article_can_add_categories(): void
    {
        $article  = $this->makeArticle();
        $category = $this->makeCategory(['title' => ['en' => 'New Category']]);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'title_en'       => $article->getTranslation('title', 'en'),
                'author'         => $article->author,
                'status'         => true,
                'blogCategories' => [$category->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('blog_article_blog_category', [
            'blog_article_id'  => $article->id,
            'blog_category_id' => $category->id,
        ]);
    }

    public function test_edit_article_can_remove_categories(): void
    {
        $article  = $this->makeArticle();
        $catOld   = $this->makeCategory(['title' => ['en' => 'Old Category']]);
        $catNew   = $this->makeCategory(['title' => ['en' => 'New Category']]);

        $article->blogCategories()->attach($catOld->id);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'title_en'       => $article->getTranslation('title', 'en'),
                'author'         => $article->author,
                'status'         => true,
                'blogCategories' => [$catNew->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();
        $this->assertCount(1, $article->blogCategories);
        $this->assertSame($catNew->id, $article->blogCategories->first()->id);
        $this->assertDatabaseMissing('blog_article_blog_category', [
            'blog_article_id'  => $article->id,
            'blog_category_id' => $catOld->id,
        ]);
    }

    // ── Delete action ──────────────────────────────────────────────────────

    public function test_delete_article_removes_record_from_database(): void
    {
        $article = $this->makeArticle();
        $id      = $article->id;

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('blog_articles', ['id' => $id]);
    }

    public function test_delete_article_removes_pivot_table_entries(): void
    {
        $article  = $this->makeArticle();
        $category = $this->makeCategory();
        $article->blogCategories()->attach($category->id);

        $articleId = $article->id;

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('blog_article_blog_category', [
            'blog_article_id' => $articleId,
        ]);
    }

    // ── Edit page: slug persistence ───────────────────────────────────────

    public function test_edit_form_pre_populates_slug_fields_from_database(): void
    {
        $article = $this->makeArticle(['title' => ['en' => 'Slug Pre-population Article']]);

        // Manually insert a slug record so we can assert pre-population
        $article->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'slug-pre-population-article'],
        );

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'slug-pre-population-article',
            ]);
    }

    public function test_edit_article_persists_slug_to_database_on_save(): void
    {
        $article = $this->makeArticle(['title' => ['en' => 'Original Slug Article']]);

        // Seed an existing slug
        $article->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'original-slug-article'],
        );

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'title_en' => 'Original Slug Article',
                'slug_en'  => 'custom-edited-slug',
                'author'   => 'Slug Persist Author',
                'status'   => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => BlogArticle::class,
            'sluggable_id'   => $article->id,
            'locale'         => 'en',
            'slug'           => 'custom-edited-slug',
        ]);
    }

    // ── Translatable fields: multi-language ────────────────────────────────

    public function test_create_article_saves_translations_for_multiple_languages(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        Livewire::test(CreateBlogArticle::class)
            ->fillForm([
                'title_en'  => 'English Title',
                'title_de'  => 'Deutsches Titel',
                'author'    => 'Multilang Author',
                'post_date' => '2024-01-01',
                'status'    => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = BlogArticle::where('author', 'Multilang Author')->first();
        $this->assertNotNull($article);
        $this->assertSame('English Title', $article->getTranslation('title', 'en'));
        $this->assertSame('Deutsches Titel', $article->getTranslation('title', 'de'));
    }

    public function test_edit_form_pre_populates_translations_for_multiple_languages(): void
    {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_default' => false]);

        $article = $this->makeArticle([
            'title'       => ['en' => 'English Edit Title', 'de' => 'Deutsches Titel'],
            'description' => ['en' => 'English Edit Desc', 'de' => 'Deutsche Beschreibung'],
        ]);

        Livewire::test(EditBlogArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet([
                'title_en'       => 'English Edit Title',
                'title_de'       => 'Deutsches Titel',
                'description_en' => 'English Edit Desc',
                'description_de' => 'Deutsche Beschreibung',
            ]);
    }
}
