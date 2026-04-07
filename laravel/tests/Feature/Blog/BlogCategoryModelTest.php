<?php

namespace Tests\Feature\Blog;

use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;
use Tests\TestCase;

class BlogCategoryModelTest extends TestCase
{
    use RefreshDatabase;

    // ── Traits & interfaces ────────────────────────────────────────────────────

    public function test_blog_category_uses_has_slugs_trait(): void
    {
        $this->assertContains(HasSlugs::class, class_uses_recursive(BlogCategory::class));
    }

    public function test_blog_category_uses_has_translations_trait(): void
    {
        $this->assertContains(HasTranslations::class, class_uses_recursive(BlogCategory::class));
    }

    public function test_blog_category_uses_has_uuids_trait(): void
    {
        $this->assertContains(HasUuids::class, class_uses_recursive(BlogCategory::class));
    }

    public function test_blog_category_implements_has_media_interface(): void
    {
        $this->assertInstanceOf(HasMedia::class, new BlogCategory());
    }

    // ── UUID primary key ──────────────────────────────────────────────────────

    public function test_blog_category_key_type_is_string(): void
    {
        $model = new BlogCategory();
        $this->assertSame('string', $model->getKeyType());
    }

    public function test_blog_category_is_not_incrementing(): void
    {
        $model = new BlogCategory();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_blog_category_generates_uuid_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'UUID Test'],
            'status' => 'active',
        ]);

        $this->assertNotNull($category->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $category->id,
        );
    }

    // ── $translatable ──────────────────────────────────────────────────────────

    public function test_blog_category_translatable_includes_required_fields(): void
    {
        $category = new BlogCategory();

        $expected = ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($expected as $field) {
            $this->assertContains($field, $category->getTranslatableAttributes());
        }
    }

    // ── $fillable ─────────────────────────────────────────────────────────────

    public function test_blog_category_fillable_includes_required_fields(): void
    {
        $category = new BlogCategory();

        $expected = ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'status'];

        foreach ($expected as $field) {
            $this->assertContains($field, $category->getFillable());
        }
    }

    // ── $casts ────────────────────────────────────────────────────────────────

    public function test_blog_category_status_is_cast_to_string(): void
    {
        $category = new BlogCategory();
        $casts = $category->getCasts();

        $this->assertArrayHasKey('status', $casts);
        $this->assertSame('string', $casts['status']);
    }

    // ── Slug generation from title ─────────────────────────────────────────────

    public function test_blog_category_generates_slug_from_title_field(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'My Blog Category'],
            'status' => 'active',
        ]);

        $slug = $category->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('my-blog-category', $slug->slug);
    }

    public function test_blog_category_generates_slugs_for_multiple_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'Technology', 'fr' => 'Technologie'],
            'status' => 'active',
        ]);

        $slugEn = $category->getSlugForLocale('en');
        $slugFr = $category->getSlugForLocale('fr');

        $this->assertNotNull($slugEn);
        $this->assertSame('technology', $slugEn->slug);

        $this->assertNotNull($slugFr);
        $this->assertSame('technologie', $slugFr->slug);
    }

    // ── Media collection ──────────────────────────────────────────────────────

    public function test_blog_category_registers_thumbnail_media_collection(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'Media Test'],
            'status' => 'active',
        ]);

        $collections = $category->getRegisteredMediaCollections();
        $names       = collect($collections)->pluck('name')->all();

        $this->assertContains('thumbnail', $names);
    }

    // ── scopeActive ────────────────────────────────────────────────────────────

    public function test_scope_active_filters_by_active_status(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        BlogCategory::create(['title' => ['en' => 'Active Cat'], 'status' => 'active']);
        BlogCategory::create(['title' => ['en' => 'Draft Cat'], 'status' => 'draft']);

        $activeCategories = BlogCategory::active()->get();

        $this->assertCount(1, $activeCategories);
        $this->assertSame('active', $activeCategories->first()->status);
    }

    // ── blogArticles relationship ──────────────────────────────────────────────

    public function test_blog_category_has_blog_articles_belongs_to_many_relationship(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'Relationship Test'],
            'status' => 'active',
        ]);

        $this->assertInstanceOf(BelongsToMany::class, $category->blogArticles());
    }

    public function test_blog_articles_relationship_uses_correct_pivot_table(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'Pivot Test'],
            'status' => 'active',
        ]);

        $relation = $category->blogArticles();

        $this->assertSame('blog_article_blog_category', $relation->getTable());
    }
}
