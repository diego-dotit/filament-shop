<?php

namespace Tests\Feature\Api\Blog;

use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected Language $defaultLang;
    protected Currency $defaultCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);

        $this->defaultLang     = Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $this->defaultCurrency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);
    }

    // ── GET /api/blog/categories — index ─────────────────────────────────────

    public function test_index_returns_200_with_paginated_response(): void
    {
        BlogCategory::factory()->count(3)->active()->create();

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_index_returns_only_active_categories(): void
    {
        BlogCategory::factory()->count(3)->active()->create();
        BlogCategory::factory()->count(2)->inactive()->create();

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_excludes_inactive_categories(): void
    {
        BlogCategory::factory()->active()->create(['title' => ['en' => 'Active Category']]);
        BlogCategory::factory()->inactive()->create(['title' => ['en' => 'Inactive Category']]);

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $slugs = collect($response->json('data'))->pluck('status');
        $this->assertTrue($slugs->every(fn ($s) => $s === 'active'));
    }

    public function test_index_returns_correct_fields_per_category(): void
    {
        BlogCategory::factory()->active()->create([
            'title'            => ['en' => 'Test Title'],
            'description'      => ['en' => 'Test Description'],
            'meta_title'       => ['en' => 'Meta Title'],
            'meta_description' => ['en' => 'Meta Description'],
            'meta_keywords'    => ['en' => 'keyword1, keyword2'],
        ]);

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
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
                        'thumbnail_url',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_index_defaults_to_15_per_page(): void
    {
        BlogCategory::factory()->count(20)->active()->create();

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_index_returns_second_page(): void
    {
        BlogCategory::factory()->count(20)->active()->create();

        $response = $this->getJson('/api/blog/categories?page=2');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_index_thumbnail_url_is_null_when_no_media(): void
    {
        BlogCategory::factory()->active()->create();

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200);
        $this->assertNull($response->json('data.0.thumbnail_url'));
    }

    public function test_index_thumbnail_url_is_string_when_media_exists(): void
    {
        Storage::fake('public');

        $category = BlogCategory::factory()->active()->create();
        $file     = UploadedFile::fake()->image('thumb.jpg');
        $category->addMedia($file)->toMediaCollection('thumbnail');

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.0.thumbnail_url'));
        $this->assertIsString($response->json('data.0.thumbnail_url'));
    }

    public function test_index_slug_resolves_to_accept_language_locale(): void
    {
        Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        $category = BlogCategory::factory()->active()->create([
            'title' => ['en' => 'Tech News', 'fr' => 'Actualités Tech'],
        ]);

        // Fetch default locale slug for reference
        $enResponse = $this->getJson('/api/blog/categories', ['Accept-Language' => 'en']);
        $enResponse->assertStatus(200);
        $enSlug = $enResponse->json('data.0.slug');

        $frResponse = $this->getJson('/api/blog/categories', ['Accept-Language' => 'fr']);
        $frResponse->assertStatus(200);
        $frSlug = $frResponse->json('data.0.slug');

        // Slugs for different locales should differ (or at least be non-null)
        $this->assertNotEmpty($enSlug);
        $this->assertNotEmpty($frSlug);
        $this->assertNotEquals($enSlug, $frSlug);
    }

    public function test_index_returns_empty_data_when_no_active_categories(): void
    {
        BlogCategory::factory()->count(3)->inactive()->create();

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
