<?php

namespace Tests\Unit\Models;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Language\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::factory()->create(['code' => 'en', 'is_default' => true]);
    }

    // ── scopePublished ─────────────────────────────────────────────────────────

    public function test_published_scope_excludes_articles_with_future_post_date(): void
    {
        BlogArticle::create([
            'title'     => ['en' => 'Future Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now()->addDay(),
        ]);

        $published = BlogArticle::published()->get();

        $this->assertCount(0, $published);
    }

    public function test_published_scope_includes_articles_with_past_post_date(): void
    {
        BlogArticle::create([
            'title'     => ['en' => 'Past Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now()->subDay(),
        ]);

        $published = BlogArticle::published()->get();

        $this->assertCount(1, $published);
    }

    public function test_published_scope_includes_articles_with_todays_post_date(): void
    {
        BlogArticle::create([
            'title'     => ['en' => 'Today Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $published = BlogArticle::published()->get();

        $this->assertCount(1, $published);
    }

    public function test_published_scope_filters_only_future_from_mixed_set(): void
    {
        BlogArticle::create([
            'title'     => ['en' => 'Past Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now()->subDays(3),
        ]);
        BlogArticle::create([
            'title'     => ['en' => 'Today Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);
        BlogArticle::create([
            'title'     => ['en' => 'Future Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now()->addDays(3),
        ]);

        $published = BlogArticle::published()->get();

        $this->assertCount(2, $published);
    }

    // ── scopeActive ────────────────────────────────────────────────────────────

    public function test_active_scope_includes_articles_with_active_status(): void
    {
        BlogArticle::create([
            'title'     => ['en' => 'Active Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $active = BlogArticle::active()->get();

        $this->assertCount(1, $active);
    }

    public function test_active_scope_excludes_articles_with_inactive_status(): void
    {
        BlogArticle::create([
            'title'     => ['en' => 'Inactive Article'],
            'author'    => 'Test Author',
            'status'    => 'inactive',
            'post_date' => now(),
        ]);

        $active = BlogArticle::active()->get();

        $this->assertCount(0, $active);
    }

    public function test_active_scope_excludes_articles_with_draft_status(): void
    {
        BlogArticle::create([
            'title'     => ['en' => 'Draft Article'],
            'author'    => 'Test Author',
            'status'    => 'draft',
            'post_date' => now(),
        ]);
        BlogArticle::create([
            'title'     => ['en' => 'Active Article'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now(),
        ]);

        $active = BlogArticle::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('active', $active->first()->status);
    }

    // ── Chained scopes ─────────────────────────────────────────────────────────

    public function test_published_and_active_scopes_can_be_chained(): void
    {
        // Active and published
        BlogArticle::create([
            'title'     => ['en' => 'Active Published'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now()->subDay(),
        ]);
        // Active but future (not published)
        BlogArticle::create([
            'title'     => ['en' => 'Active Future'],
            'author'    => 'Test Author',
            'status'    => 'active',
            'post_date' => now()->addDay(),
        ]);
        // Inactive and published
        BlogArticle::create([
            'title'     => ['en' => 'Inactive Published'],
            'author'    => 'Test Author',
            'status'    => 'inactive',
            'post_date' => now()->subDay(),
        ]);

        $results = BlogArticle::published()->active()->get();

        $this->assertCount(1, $results);
        $this->assertSame('Active Published', $results->first()->getTranslation('title', 'en'));
    }
}
