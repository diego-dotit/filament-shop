<?php

namespace Tests\Feature\Api\Blog;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Http\Resources\Api\Blog\BlogArticleResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BlogArticleResourceTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createLanguage(string $code, bool $isDefault = true): Language
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
            'post_date'        => '2024-06-15',
        ], $overrides));
    }

    private function makeRequest(string $locale = 'en'): Request
    {
        $request = Request::create('/api/blog/articles');
        app()->setLocale($locale);

        return $request;
    }

    // ── Structure: all required fields present ────────────────────────────────

    public function test_resource_returns_all_required_fields(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle();
        $request = $this->makeRequest();

        $data = (new BlogArticleResource($article))->toArray($request);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('meta_title', $data);
        $this->assertArrayHasKey('meta_description', $data);
        $this->assertArrayHasKey('meta_keywords', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('author', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('post_date', $data);
        $this->assertArrayHasKey('thumbnail_url', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    // ── Translatable fields resolved to single locale string ──────────────────

    public function test_translatable_fields_resolved_to_single_locale_string(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle([
            'title'            => ['en' => 'English Title', 'fr' => 'Titre Français'],
            'description'      => ['en' => 'English Description'],
            'meta_title'       => ['en' => 'English Meta Title'],
            'meta_description' => ['en' => 'English Meta Description'],
            'meta_keywords'    => ['en' => 'en-keyword'],
        ]);
        $request = $this->makeRequest('en');

        $data = (new BlogArticleResource($article))->toArray($request);

        $this->assertSame('English Title', $data['title']);
        $this->assertSame('English Description', $data['description']);
        $this->assertSame('English Meta Title', $data['meta_title']);
        $this->assertSame('English Meta Description', $data['meta_description']);
        $this->assertSame('en-keyword', $data['meta_keywords']);
        // Verify it's a string, not an array
        $this->assertIsString($data['title']);
        $this->assertIsString($data['description']);
    }

    // ── post_date formatted as YYYY-MM-DD ─────────────────────────────────────

    public function test_post_date_formatted_as_yyyy_mm_dd(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle(['post_date' => '2024-03-20']);
        $request = $this->makeRequest();

        $data = (new BlogArticleResource($article))->toArray($request);

        $this->assertSame('2024-03-20', $data['post_date']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['post_date']);
    }

    // ── thumbnail_url is null when no media ───────────────────────────────────

    public function test_thumbnail_url_is_null_when_no_media_attached(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle();
        $request = $this->makeRequest();

        $data = (new BlogArticleResource($article))->toArray($request);

        $this->assertNull($data['thumbnail_url']);
    }

    // ── categories key absent when relation not loaded ────────────────────────

    public function test_categories_key_absent_when_relation_not_loaded(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle();
        // Do NOT eager load blogCategories
        $freshArticle = BlogArticle::find($article->id);
        $request = $this->makeRequest();

        $resource = new BlogArticleResource($freshArticle);
        $response = $resource->toResponse($request);
        $json = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('categories', $json['data']);
    }

    // ── categories included when relation is loaded ───────────────────────────

    public function test_categories_included_when_relation_loaded(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle();
        $category = BlogCategory::factory()->create(['title' => ['en' => 'Tech']]);
        $article->blogCategories()->attach($category->id);

        $articleWithCategories = BlogArticle::with('blogCategories')->find($article->id);
        $request = $this->makeRequest();

        $data = (new BlogArticleResource($articleWithCategories))->toArray($request);

        $this->assertArrayHasKey('categories', $data);
        $categories = $data['categories'];
        $this->assertNotEmpty($categories);
    }

    // ── categories contain id, slug, title ────────────────────────────────────

    public function test_categories_contain_required_nested_fields(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle();
        $category = BlogCategory::factory()->create(['title' => ['en' => 'Science']]);
        $article->blogCategories()->attach($category->id);

        $articleWithCategories = BlogArticle::with('blogCategories')->find($article->id);
        $request = $this->makeRequest();

        // Resolve nested resources through HTTP response for full serialization
        $resource = new BlogArticleResource($articleWithCategories);
        $response = $resource->toResponse($request);
        $responseData = json_decode($response->getContent(), true);

        $categoryData = $responseData['data']['categories'][0];
        $this->assertArrayHasKey('id', $categoryData);
        $this->assertArrayHasKey('slug', $categoryData);
        $this->assertArrayHasKey('title', $categoryData);
    }

    // ── status and author are strings ─────────────────────────────────────────

    public function test_status_and_author_are_strings(): void
    {
        $this->createLanguage('en');
        $article = $this->createArticle(['status' => 'active', 'author' => 'Jane Doe']);
        $request = $this->makeRequest();

        $data = (new BlogArticleResource($article))->toArray($request);

        $this->assertIsString($data['status']);
        $this->assertIsString($data['author']);
        $this->assertSame('active', $data['status']);
        $this->assertSame('Jane Doe', $data['author']);
    }
}
