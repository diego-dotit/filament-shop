<?php

namespace Tests\Feature\Api\Blog;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogArticleShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear default Accept-Language header injected by Symfony test client
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createLanguage(string $code, bool $isDefault = false): Language
    {
        return Language::factory()->create(['code' => $code, 'is_default' => $isDefault]);
    }

    private function createArticle(array $overrides = []): BlogArticle
    {
        return BlogArticle::create(array_merge([
            'title'            => ['en' => 'Test Article'],
            'description'      => ['en' => 'Test description'],
            'meta_title'       => ['en' => 'Meta Title'],
            'meta_description' => ['en' => 'Meta description'],
            'meta_keywords'    => ['en' => 'keyword1, keyword2'],
            'author'           => 'Test Author',
            'status'           => 'active',
            'post_date'        => now()->toDateString(),
        ], $overrides));
    }

    // ── GET /api/blog/articles/{slug} — returns 200 ───────────────────────────

    public function test_show_returns_200_with_article_data_by_slug(): void
    {
        $this->createLanguage('en', true);

        $article = $this->createArticle(['title' => ['en' => 'Hello World']]);

        // Slug is auto-generated as 'hello-world' from title
        $response = $this->getJson('/api/blog/articles/hello-world');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.author', 'Test Author')
            ->assertJsonPath('data.status', 'active');
    }

    // ── Response structure matches BlogArticleResource ────────────────────────

    public function test_show_response_structure_matches_blog_article_resource(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle(['title' => ['en' => 'Structure Test']]);

        $response = $this->getJson('/api/blog/articles/structure-test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'meta_title',
                    'meta_description',
                    'meta_keywords',
                    'slug',
                    'author',
                    'status',
                    'post_date',
                    'thumbnail_url',
                    'categories',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    // ── 404 for non-existent slug ─────────────────────────────────────────────

    public function test_show_returns_404_for_non_existent_slug(): void
    {
        $this->createLanguage('en', true);

        $response = $this->getJson('/api/blog/articles/does-not-exist');

        $response->assertStatus(404);
    }

    // ── 404 for inactive article ──────────────────────────────────────────────

    public function test_show_returns_404_for_inactive_article(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle([
            'title'  => ['en' => 'Inactive Article'],
            'status' => 'inactive',
        ]);

        $response = $this->getJson('/api/blog/articles/inactive-article');

        $response->assertStatus(404);
    }

    // ── 404 for future post_date ──────────────────────────────────────────────

    public function test_show_returns_404_for_article_with_future_post_date(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle([
            'title'     => ['en' => 'Future Article'],
            'post_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->getJson('/api/blog/articles/future-article');

        $response->assertStatus(404);
    }

    // ── Categories relationship loaded and included ───────────────────────────

    public function test_show_includes_categories_relationship(): void
    {
        $this->createLanguage('en', true);

        $article  = $this->createArticle(['title' => ['en' => 'Category Article']]);
        $category = BlogCategory::factory()->create(['title' => ['en' => 'Technology']]);
        $article->blogCategories()->attach($category->id);

        $response = $this->getJson('/api/blog/articles/category-article');

        $response->assertStatus(200);

        $categories = $response->json('data.categories');
        $this->assertIsArray($categories);
        $this->assertCount(1, $categories);
        $this->assertArrayHasKey('id', $categories[0]);
        $this->assertArrayHasKey('title', $categories[0]);
    }

    // ── Locale resolution via Accept-Language header ──────────────────────────

    public function test_show_resolves_locale_via_accept_language_header(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);

        $this->createArticle([
            'title'       => ['en' => 'English Title', 'fr' => 'Titre Français'],
            'description' => ['en' => 'English desc', 'fr' => 'Description française'],
        ]);

        // 'en' slug should exist as 'english-title'
        $response = $this->getJson('/api/blog/articles/english-title', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Titre Français')
            ->assertJsonPath('data.description', 'Description française');
    }

    // ── Thumbnail URL resolves correctly ──────────────────────────────────────

    public function test_show_thumbnail_url_is_null_when_no_media(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle(['title' => ['en' => 'No Thumb Article']]);

        $response = $this->getJson('/api/blog/articles/no-thumb-article');

        $response->assertStatus(200)
            ->assertJsonPath('data.thumbnail_url', null);
    }

    public function test_show_thumbnail_url_resolves_when_media_exists(): void
    {
        $this->createLanguage('en', true);
        Storage::fake('public');

        $article = $this->createArticle(['title' => ['en' => 'Thumb Article']]);
        $image   = \Illuminate\Http\UploadedFile::fake()->image('thumb.jpg');
        $article->addMedia($image)->toMediaCollection('thumbnail');

        $response = $this->getJson('/api/blog/articles/thumb-article');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.thumbnail_url'));
        $this->assertIsString($response->json('data.thumbnail_url'));
    }

    // ── All translatable fields resolve to requested locale ───────────────────

    public function test_show_all_translatable_fields_resolve_to_requested_locale(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);

        $this->createArticle([
            'title'            => ['en' => 'My Article', 'fr' => 'Mon Article'],
            'description'      => ['en' => 'English desc', 'fr' => 'Description en français'],
            'meta_title'       => ['en' => 'EN Meta', 'fr' => 'FR Meta'],
            'meta_description' => ['en' => 'EN Meta Desc', 'fr' => 'FR Meta Desc'],
            'meta_keywords'    => ['en' => 'en-key', 'fr' => 'fr-key'],
        ]);

        $response = $this->getJson('/api/blog/articles/my-article', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Mon Article')
            ->assertJsonPath('data.description', 'Description en français')
            ->assertJsonPath('data.meta_title', 'FR Meta')
            ->assertJsonPath('data.meta_description', 'FR Meta Desc')
            ->assertJsonPath('data.meta_keywords', 'fr-key');
    }
}
