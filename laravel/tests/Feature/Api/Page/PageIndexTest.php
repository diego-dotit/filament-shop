<?php

namespace Tests\Feature\Api\Page;

use App\Domains\Language\Models\Language;
use App\Domains\Page\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageIndexTest extends TestCase
{
    use RefreshDatabase;

    private Language $defaultLang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);

        $this->defaultLang = Language::create([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => true,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createPage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'title'            => ['en' => 'Test Page'],
            'description'      => ['en' => 'Test description'],
            'meta_title'       => ['en' => 'Meta Title'],
            'meta_description' => ['en' => 'Meta description'],
            'meta_keywords'    => ['en' => 'keyword1, keyword2'],
            'status'           => 'active',
        ], $overrides));
    }

    // ── GET /api/pages — 200 with paginated response ───────────────────────────

    public function test_index_returns_200_with_paginated_response(): void
    {
        $this->createPage(['title' => ['en' => 'Page One']]);
        $this->createPage(['title' => ['en' => 'Page Two']]);

        $response = $this->getJson('/api/pages');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['success', 'data', 'message', 'links', 'meta']);
    }

    // ── Response structure ────────────────────────────────────────────────────

    public function test_index_returns_correct_response_structure(): void
    {
        $this->createPage();

        $response = $this->getJson('/api/pages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'meta_title',
                        'meta_description',
                        'meta_keywords',
                        'slug',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    // ── Only active pages returned ────────────────────────────────────────────

    public function test_index_returns_only_active_pages(): void
    {
        $this->createPage(['title' => ['en' => 'Active Page'], 'status' => 'active']);
        $this->createPage(['title' => ['en' => 'Inactive Page'], 'status' => 'inactive']);
        $this->createPage(['title' => ['en' => 'Draft Page'], 'status' => 'draft']);

        $response = $this->getJson('/api/pages');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Active Page')
            ->assertJsonPath('data.0.status', 'active');
    }

    // ── Inactive pages excluded ───────────────────────────────────────────────

    public function test_index_excludes_inactive_pages(): void
    {
        $this->createPage(['title' => ['en' => 'Visible'], 'status' => 'active']);
        $this->createPage(['title' => ['en' => 'Hidden'], 'status' => 'inactive']);

        $response = $this->getJson('/api/pages');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Visible', $titles);
        $this->assertNotContains('Hidden', $titles);
    }

    // ── Response includes all expected fields ─────────────────────────────────

    public function test_index_response_includes_all_expected_fields_per_page(): void
    {
        $this->createPage([
            'title'            => ['en' => 'About Us'],
            'description'      => ['en' => 'About description'],
            'meta_title'       => ['en' => 'About Meta'],
            'meta_description' => ['en' => 'About meta desc'],
            'meta_keywords'    => ['en' => 'about, us'],
            'status'           => 'active',
        ]);

        $response = $this->getJson('/api/pages');

        $response->assertStatus(200);

        $page = $response->json('data.0');

        $this->assertArrayHasKey('id', $page);
        $this->assertArrayHasKey('title', $page);
        $this->assertArrayHasKey('description', $page);
        $this->assertArrayHasKey('meta_title', $page);
        $this->assertArrayHasKey('meta_description', $page);
        $this->assertArrayHasKey('meta_keywords', $page);
        $this->assertArrayHasKey('slug', $page);
        $this->assertArrayHasKey('status', $page);
        $this->assertArrayHasKey('created_at', $page);
        $this->assertArrayHasKey('updated_at', $page);

        $this->assertSame('About Us', $page['title']);
        $this->assertSame('About description', $page['description']);
        $this->assertSame('About Meta', $page['meta_title']);
        $this->assertSame('About meta desc', $page['meta_description']);
        $this->assertSame('about, us', $page['meta_keywords']);
        $this->assertSame('about-us', $page['slug']);
        $this->assertSame('active', $page['status']);
    }

    // ── Slug resolves to current locale ──────────────────────────────────────

    public function test_index_slug_resolves_to_current_locale(): void
    {
        Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        $this->createPage([
            'title' => ['en' => 'Contact Us', 'fr' => 'Contactez-Nous'],
        ]);

        $response = $this->getJson('/api/pages', ['Accept-Language' => 'fr']);

        $response->assertStatus(200);
        $this->assertSame('contactez-nous', $response->json('data.0.slug'));
    }

    // ── Accept-Language header resolves translatable fields ───────────────────

    public function test_index_returns_translated_fields_based_on_accept_language_header(): void
    {
        Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        $this->createPage([
            'title'       => ['en' => 'About Us', 'fr' => 'À propos de nous'],
            'description' => ['en' => 'English description', 'fr' => 'Description française'],
        ]);

        $response = $this->getJson('/api/pages', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'À propos de nous')
            ->assertJsonPath('data.0.description', 'Description française');
    }

    // ── Falls back to default locale ──────────────────────────────────────────

    public function test_index_falls_back_to_default_locale_when_language_not_found(): void
    {
        $this->createPage(['title' => ['en' => 'About Us']]);

        $response = $this->getJson('/api/pages', ['Accept-Language' => 'xx']);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'About Us');
    }

    // ── Pagination: ?per_page ─────────────────────────────────────────────────

    public function test_index_paginates_results_with_per_page_parameter(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createPage(['title' => ['en' => "Page {$i}"]]);
        }

        $response = $this->getJson('/api/pages?per_page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);
    }

    // ── Pagination: ?page=2 ───────────────────────────────────────────────────

    public function test_index_returns_correct_page_with_page_parameter(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createPage(['title' => ['en' => "Page {$i}"]]);
        }

        $response = $this->getJson('/api/pages?per_page=3&page=2');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    // ── Empty list when no active pages ──────────────────────────────────────

    public function test_index_returns_empty_data_when_no_active_pages(): void
    {
        $this->createPage(['status' => 'inactive']);

        $response = $this->getJson('/api/pages');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
