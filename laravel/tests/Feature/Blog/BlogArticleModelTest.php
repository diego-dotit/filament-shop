<?php

namespace Tests\Feature\Blog;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;
use Tests\TestCase;

class BlogArticleModelTest extends TestCase
{
    use RefreshDatabase;

    // ── Traits & interfaces ────────────────────────────────────────────────────

    public function test_blog_article_uses_has_slugs_trait(): void
    {
        $this->assertContains(HasSlugs::class, class_uses_recursive(BlogArticle::class));
    }

    public function test_blog_article_uses_has_translations_trait(): void
    {
        $this->assertContains(HasTranslations::class, class_uses_recursive(BlogArticle::class));
    }

    public function test_blog_article_uses_has_uuids_trait(): void
    {
        $this->assertContains(HasUuids::class, class_uses_recursive(BlogArticle::class));
    }

    public function test_blog_article_implements_has_media_interface(): void
    {
        $this->assertInstanceOf(HasMedia::class, new BlogArticle());
    }

    // ── UUID primary key ──────────────────────────────────────────────────────

    public function test_blog_article_key_type_is_string(): void
    {
        $model = new BlogArticle();
        $this->assertSame('string', $model->getKeyType());
    }

    public function test_blog_article_is_not_incrementing(): void
    {
        $model = new BlogArticle();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_blog_article_generates_uuid_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $article = BlogArticle::create([
            'title'     => ['en' => 'UUID Test Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $this->assertNotNull($article->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $article->id,
        );
    }

    // ── $translatable ──────────────────────────────────────────────────────────

    public function test_blog_article_translatable_includes_required_fields(): void
    {
        $article = new BlogArticle();

        $expected = ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($expected as $field) {
            $this->assertContains($field, $article->getTranslatableAttributes());
        }
    }

    // ── $fillable ─────────────────────────────────────────────────────────────

    public function test_blog_article_fillable_includes_required_fields(): void
    {
        $article = new BlogArticle();

        $expected = [
            'title', 'description', 'meta_title', 'meta_description', 'meta_keywords',
            'author', 'status', 'post_date',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $article->getFillable());
        }
    }

    // ── $casts ────────────────────────────────────────────────────────────────

    public function test_blog_article_status_is_cast_to_string(): void
    {
        $article = new BlogArticle();
        $casts   = $article->getCasts();

        $this->assertArrayHasKey('status', $casts);
        $this->assertSame('string', $casts['status']);
    }

    public function test_blog_article_post_date_is_cast_to_date(): void
    {
        $article = new BlogArticle();
        $casts   = $article->getCasts();

        $this->assertArrayHasKey('post_date', $casts);
        $this->assertSame('date', $casts['post_date']);
    }

    // ── Slug generation from title ─────────────────────────────────────────────

    public function test_blog_article_generates_slug_from_title_field(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $article = BlogArticle::create([
            'title'     => ['en' => 'My Blog Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $slug = $article->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('my-blog-article', $slug->slug);
    }

    // ── Media collection ──────────────────────────────────────────────────────

    public function test_blog_article_registers_thumbnail_media_collection(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $article = BlogArticle::create([
            'title'     => ['en' => 'Media Test'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $collections = $article->getRegisteredMediaCollections();
        $names       = collect($collections)->pluck('name')->all();

        $this->assertContains('thumbnail', $names);
    }

    public function test_blog_article_thumbnail_collection_is_single_file(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $article = BlogArticle::create([
            'title'     => ['en' => 'Single File Test'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $collections = $article->getRegisteredMediaCollections();
        $thumbnail   = collect($collections)->firstWhere('name', 'thumbnail');

        $this->assertNotNull($thumbnail);
        $this->assertTrue($thumbnail->singleFile);
    }

    // ── scopeActive ────────────────────────────────────────────────────────────

    public function test_scope_active_filters_by_active_status(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        BlogArticle::create(['title' => ['en' => 'Active Article'], 'author' => 'Test Author', 'status' => 'active', 'post_date' => now()]);
        BlogArticle::create(['title' => ['en' => 'Draft Article'], 'author' => 'Test Author', 'status' => 'draft', 'post_date' => now()]);

        $activeArticles = BlogArticle::active()->get();

        $this->assertCount(1, $activeArticles);
        $this->assertSame('active', $activeArticles->first()->status);
    }

    // ── scopePublished ─────────────────────────────────────────────────────────

    public function test_scope_published_filters_by_post_date_lte_today(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        BlogArticle::create(['title' => ['en' => 'Published Article'], 'author' => 'Test Author', 'status' => 'active', 'post_date' => now()->subDay()]);
        BlogArticle::create(['title' => ['en' => 'Future Article'], 'author' => 'Test Author', 'status' => 'active', 'post_date' => now()->addDay()]);

        $published = BlogArticle::published()->get();

        $this->assertCount(1, $published);
        $this->assertSame('Published Article', $published->first()->getTranslation('title', 'en'));
    }

    public function test_scope_published_includes_articles_posted_today(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        BlogArticle::create(['title' => ['en' => 'Today Article'], 'author' => 'Test Author', 'status' => 'active', 'post_date' => now()]);

        $published = BlogArticle::published()->get();

        $this->assertCount(1, $published);
    }

    // ── blogCategories relationship ────────────────────────────────────────────

    public function test_blog_article_has_blog_categories_belongs_to_many_relationship(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $article = BlogArticle::create([
            'title'     => ['en' => 'Relationship Test'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $this->assertInstanceOf(BelongsToMany::class, $article->blogCategories());
    }

    public function test_blog_categories_relationship_uses_correct_pivot_table(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $article = BlogArticle::create([
            'title'     => ['en' => 'Pivot Test'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $relation = $article->blogCategories();

        $this->assertSame('blog_article_blog_category', $relation->getTable());
    }

    public function test_blog_article_can_be_attached_to_blog_category(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $article = BlogArticle::create([
            'title'     => ['en' => 'Attach Test'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'Test Category'],
            'status' => 'active',
        ]);

        $article->blogCategories()->attach($category->id);

        $this->assertCount(1, $article->blogCategories()->get());
        $this->assertSame($category->id, $article->blogCategories()->first()->id);
    }
}
