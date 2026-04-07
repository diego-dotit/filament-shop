<?php

namespace Tests\Feature\Api\Blog;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogArticleIndexTest extends TestCase
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
        return Language::create([
            'code'       => $code,
            'name'       => strtoupper($code),
            'is_default' => $isDefault,
        ]);
    }

    private function createArticle(array $overrides = []): BlogArticle
    {
        return BlogArticle::create(array_merge([
            'title'       => ['en' => 'Test Article ' . uniqid()],
            'description' => ['en' => 'Test description'],
            'author'      => 'Test Author',
            'status'      => 'active',
            'post_date'   => now()->subDay()->toDateString(),
        ], $overrides));
    }

    private function createCategory(array $overrides = []): BlogCategory
    {
        return BlogCategory::create(array_merge([
            'title'  => ['en' => 'Test Category ' . uniqid()],
            'status' => 'active',
        ], $overrides));
    }

    // ── GET /api/blog/articles — basic listing ────────────────────────────────

    public function test_index_returns_200_with_paginated_response(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle();
        $this->createArticle();

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_index_returns_only_published_active_articles(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle(); // active + past post_date = published
        $this->createArticle(); // active + past post_date = published
        $this->createArticle(['status' => 'inactive']); // inactive — excluded
        $this->createArticle(['post_date' => now()->addDay()->toDateString()]); // future — excluded

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_excludes_inactive_articles(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle(['status' => 'active']);
        $this->createArticle(['status' => 'inactive']);

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_index_excludes_articles_with_future_post_date(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle(['post_date' => now()->subDay()->toDateString()]);      // included
        $this->createArticle(['post_date' => now()->addDay()->toDateString()]);      // excluded
        $this->createArticle(['post_date' => now()->addWeek()->toDateString()]);     // excluded

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ── Response structure ────────────────────────────────────────────────────

    public function test_index_response_includes_expected_article_fields(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle([
            'title'            => ['en' => 'My Article'],
            'description'      => ['en' => 'Some description'],
            'meta_title'       => ['en' => 'Meta Title'],
            'meta_description' => ['en' => 'Meta Desc'],
            'meta_keywords'    => ['en' => 'keyword1, keyword2'],
            'author'           => 'Jane Doe',
            'status'           => 'active',
            'post_date'        => now()->subDay()->toDateString(),
        ]);

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
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
                ]],
                'links',
                'meta',
            ]);
    }

    public function test_index_thumbnail_url_is_null_when_no_media(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle();

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200);
        $this->assertNull($response->json('data.0.thumbnail_url'));
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    public function test_index_defaults_to_15_per_page(): void
    {
        $this->createLanguage('en', true);

        for ($i = 0; $i < 20; $i++) {
            $this->createArticle();
        }

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_index_accepts_per_page_param(): void
    {
        $this->createLanguage('en', true);

        for ($i = 0; $i < 10; $i++) {
            $this->createArticle();
        }

        $response = $this->getJson('/api/blog/articles?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_index_accepts_page_param(): void
    {
        $this->createLanguage('en', true);

        for ($i = 0; $i < 10; $i++) {
            $this->createArticle();
        }

        $response = $this->getJson('/api/blog/articles?per_page=5&page=2');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    // ── Categories relationship ───────────────────────────────────────────────

    public function test_index_includes_categories_nested_in_article(): void
    {
        $this->createLanguage('en', true);

        $category = $this->createCategory(['title' => ['en' => 'Tech News']]);
        $article  = $this->createArticle();
        $article->blogCategories()->attach($category->id);

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200);

        $categories = $response->json('data.0.categories');
        $this->assertIsArray($categories);
        $this->assertCount(1, $categories);
        $this->assertArrayHasKey('id', $categories[0]);
        $this->assertArrayHasKey('slug', $categories[0]);
        $this->assertArrayHasKey('title', $categories[0]);
    }

    public function test_index_returns_empty_categories_array_when_no_categories(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle();

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200);
        $categories = $response->json('data.0.categories');
        $this->assertIsArray($categories);
        $this->assertCount(0, $categories);
    }

    // ── Category slug filter ──────────────────────────────────────────────────

    public function test_category_slug_filter_returns_only_articles_in_that_category(): void
    {
        $this->createLanguage('en', true);

        $tech    = $this->createCategory(['title' => ['en' => 'Tech']]);
        $cooking = $this->createCategory(['title' => ['en' => 'Cooking']]);

        $article1 = $this->createArticle(['title' => ['en' => 'Tech Article One']]);
        $article2 = $this->createArticle(['title' => ['en' => 'Tech Article Two']]);
        $article3 = $this->createArticle(['title' => ['en' => 'Cooking Article']]);

        $article1->blogCategories()->attach($tech->id);
        $article2->blogCategories()->attach($tech->id);
        $article3->blogCategories()->attach($cooking->id);

        // The slug for 'Tech' category is auto-generated as 'tech'
        $techSlug = $tech->slugs()->where('locale', 'en')->value('slug');

        $response = $this->getJson("/api/blog/articles?category_slug={$techSlug}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $cookingSlug = $cooking->slugs()->where('locale', 'en')->value('slug');
        $response2 = $this->getJson("/api/blog/articles?category_slug={$cookingSlug}");

        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_category_slug_filter_returns_empty_when_no_articles_match(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle();

        $response = $this->getJson('/api/blog/articles?category_slug=nonexistent-category');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_category_slug_filter_excludes_articles_not_in_category(): void
    {
        $this->createLanguage('en', true);

        $tech = $this->createCategory(['title' => ['en' => 'Tech']]);

        $techArticle      = $this->createArticle(['title' => ['en' => 'Tech Article']]);
        $unrelatedArticle = $this->createArticle(['title' => ['en' => 'Unrelated']]);

        $techArticle->blogCategories()->attach($tech->id);

        $techSlug = $tech->slugs()->where('locale', 'en')->value('slug');

        $response = $this->getJson("/api/blog/articles?category_slug={$techSlug}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertNotContains('Unrelated', $titles->toArray());
    }

    // ── Locale handling ───────────────────────────────────────────────────────

    public function test_index_respects_accept_language_header(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);

        $this->createArticle([
            'title'       => ['en' => 'English Title', 'fr' => 'Titre Français'],
            'description' => ['en' => 'English Desc', 'fr' => 'Description Française'],
        ]);

        $response = $this->getJson('/api/blog/articles', ['Accept-Language' => 'fr']);

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Titre Français'));
    }

    public function test_index_uses_default_locale_when_no_accept_language_header(): void
    {
        $this->createLanguage('en', true);

        $this->createArticle([
            'title' => ['en' => 'English Title'],
        ]);

        $response = $this->getJson('/api/blog/articles');

        $response->assertStatus(200);
        $title = $response->json('data.0.title');
        $this->assertEquals('English Title', $title);
    }
}
