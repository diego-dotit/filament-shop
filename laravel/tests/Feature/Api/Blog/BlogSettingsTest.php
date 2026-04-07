<?php

namespace Tests\Feature\Api\Blog;

use App\Domains\Language\Models\Language;
use App\Settings\GeneralBlogSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Language $defaultLang;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear default Accept-Language header injected by Symfony test client
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);

        $this->defaultLang = Language::create([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => true,
        ]);

        // Seed settings with known values
        tap(app(GeneralBlogSettings::class), function (GeneralBlogSettings $s): void {
            $s->blog_title       = ['en' => 'My Blog', 'fr' => 'Mon Blog'];
            $s->blog_description = ['en' => 'A blog about things', 'fr' => 'Un blog sur les choses'];
            $s->meta_title       = ['en' => 'SEO Title', 'fr' => 'Titre SEO'];
            $s->meta_description = ['en' => 'SEO Description', 'fr' => 'Description SEO'];
            $s->meta_keywords    = ['en' => 'keyword1, keyword2', 'fr' => 'motclé1, motclé2'];
            $s->slug             = ['en' => 'blog', 'fr' => 'blogue'];
            $s->articles_per_page = 5;
        })->save();
    }

    // ── GET /api/blog/settings — status ──────────────────────────────────────

    public function test_settings_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/blog/settings');

        $response->assertStatus(200);
    }

    // ── Response structure ────────────────────────────────────────────────────

    public function test_settings_response_includes_all_required_fields(): void
    {
        $response = $this->getJson('/api/blog/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'blog_title',
                    'blog_description',
                    'meta_title',
                    'meta_description',
                    'meta_keywords',
                    'slug',
                    'articles_per_page',
                ],
            ]);
    }

    public function test_articles_per_page_is_integer(): void
    {
        $response = $this->getJson('/api/blog/settings');

        $response->assertStatus(200);
        $this->assertIsInt($response->json('data.articles_per_page'));
    }

    public function test_slug_is_string(): void
    {
        $response = $this->getJson('/api/blog/settings');

        $response->assertStatus(200);
        $this->assertIsString($response->json('data.slug'));
    }

    public function test_translatable_fields_are_strings_not_arrays(): void
    {
        $response = $this->getJson('/api/blog/settings');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertIsString($data['blog_title'], 'blog_title must be a string');
        $this->assertIsString($data['blog_description'], 'blog_description must be a string');
        $this->assertIsString($data['meta_title'], 'meta_title must be a string');
        $this->assertIsString($data['meta_description'], 'meta_description must be a string');
        $this->assertIsString($data['meta_keywords'], 'meta_keywords must be a string');
        $this->assertIsString($data['slug'], 'slug must be a string');
    }

    // ── Locale resolution via Accept-Language ────────────────────────────────

    public function test_default_locale_used_when_no_accept_language_header(): void
    {
        $response = $this->getJson('/api/blog/settings');

        $response->assertStatus(200)
            ->assertJsonPath('data.blog_title', 'My Blog')
            ->assertJsonPath('data.blog_description', 'A blog about things')
            ->assertJsonPath('data.meta_title', 'SEO Title')
            ->assertJsonPath('data.meta_description', 'SEO Description')
            ->assertJsonPath('data.meta_keywords', 'keyword1, keyword2')
            ->assertJsonPath('data.slug', 'blog');
    }

    public function test_accept_language_header_resolves_translatable_fields_to_requested_locale(): void
    {
        Language::create([
            'code'       => 'fr',
            'name'       => 'French',
            'is_default' => false,
        ]);

        $response = $this->getJson('/api/blog/settings', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.blog_title', 'Mon Blog')
            ->assertJsonPath('data.blog_description', 'Un blog sur les choses')
            ->assertJsonPath('data.meta_title', 'Titre SEO')
            ->assertJsonPath('data.meta_description', 'Description SEO')
            ->assertJsonPath('data.meta_keywords', 'motclé1, motclé2')
            ->assertJsonPath('data.slug', 'blogue');
    }

    public function test_articles_per_page_reflects_saved_value(): void
    {
        $response = $this->getJson('/api/blog/settings');

        $response->assertStatus(200)
            ->assertJsonPath('data.articles_per_page', 5);
    }
}
