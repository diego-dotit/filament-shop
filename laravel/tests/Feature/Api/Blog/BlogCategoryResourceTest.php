<?php

namespace Tests\Feature\Api\Blog;

use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Http\Resources\Api\Blog\BlogCategoryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BlogCategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    private Language $lang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lang = Language::factory()->create(['code' => 'en', 'is_default' => true]);
    }

    // ── toArray structure ─────────────────────────────────────────────────────

    public function test_resource_contains_all_required_keys(): void
    {
        $category = BlogCategory::create([
            'title'  => ['en' => 'Tech News'],
            'status' => 'active',
        ]);

        $request  = $this->makeRequest('en');
        $resource = new BlogCategoryResource($category);

        $data = $resource->toArray($request);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('meta_title', $data);
        $this->assertArrayHasKey('meta_description', $data);
        $this->assertArrayHasKey('meta_keywords', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('thumbnail_url', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_id_is_a_uuid_string(): void
    {
        $category = BlogCategory::create([
            'title'  => ['en' => 'UUID Category'],
            'status' => 'active',
        ]);

        $data = $this->toArray($category, 'en');

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $data['id'],
        );
    }

    public function test_translatable_fields_resolve_to_single_locale_value(): void
    {
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $category = BlogCategory::create([
            'title'            => ['en' => 'Technology', 'fr' => 'Technologie'],
            'description'      => ['en' => 'Tech desc', 'fr' => 'Desc tech'],
            'meta_title'       => ['en' => 'Meta EN', 'fr' => 'Meta FR'],
            'meta_description' => ['en' => 'MetaDesc EN', 'fr' => 'MetaDesc FR'],
            'meta_keywords'    => ['en' => 'tech,news', 'fr' => 'tech,actualités'],
            'status'           => 'active',
        ]);

        $dataEn = $this->toArray($category, 'en');
        $dataFr = $this->toArray($category, 'fr');

        $this->assertSame('Technology', $dataEn['title']);
        $this->assertSame('Technologie', $dataFr['title']);

        $this->assertSame('Tech desc', $dataEn['description']);
        $this->assertSame('Desc tech', $dataFr['description']);

        $this->assertSame('Meta EN', $dataEn['meta_title']);
        $this->assertSame('Meta FR', $dataFr['meta_title']);

        $this->assertSame('MetaDesc EN', $dataEn['meta_description']);
        $this->assertSame('MetaDesc FR', $dataFr['meta_description']);

        $this->assertSame('tech,news', $dataEn['meta_keywords']);
        $this->assertSame('tech,actualités', $dataFr['meta_keywords']);
    }

    public function test_slug_resolved_via_get_slug_for_locale(): void
    {
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'Technology', 'fr' => 'Technologie'],
            'status' => 'active',
        ]);

        $dataEn = $this->toArray($category, 'en');
        $dataFr = $this->toArray($category, 'fr');

        $this->assertSame('technology', $dataEn['slug']);
        $this->assertSame('technologie', $dataFr['slug']);
    }

    public function test_status_is_returned_as_string(): void
    {
        $active   = BlogCategory::create(['title' => ['en' => 'Active'],   'status' => 'active']);
        $inactive = BlogCategory::create(['title' => ['en' => 'Inactive'], 'status' => 'inactive']);

        $dataActive   = $this->toArray($active, 'en');
        $dataInactive = $this->toArray($inactive, 'en');

        $this->assertSame('active', $dataActive['status']);
        $this->assertSame('inactive', $dataInactive['status']);
    }

    public function test_thumbnail_url_is_null_when_no_media_attached(): void
    {
        $category = BlogCategory::create([
            'title'  => ['en' => 'No Media'],
            'status' => 'active',
        ]);

        $data = $this->toArray($category, 'en');

        $this->assertNull($data['thumbnail_url']);
    }

    public function test_lang_resolution_reads_from_request_attributes(): void
    {
        Language::factory()->create(['code' => 'de', 'is_default' => false]);

        $category = BlogCategory::create([
            'title'  => ['en' => 'Hello', 'de' => 'Hallo'],
            'status' => 'active',
        ]);

        $data = $this->toArray($category, 'de');

        $this->assertSame('Hallo', $data['title']);
    }

    public function test_lang_falls_back_to_app_locale_when_no_lang_attribute(): void
    {
        $category = BlogCategory::create([
            'title'  => ['en' => 'Fallback Title'],
            'status' => 'active',
        ]);

        // Request with no lang attribute set
        $request  = Request::create('/');
        $resource = new BlogCategoryResource($category);
        $data     = $resource->toArray($request);

        $this->assertSame('Fallback Title', $data['title']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeRequest(string $langCode): Request
    {
        $lang       = Language::where('code', $langCode)->firstOrFail();
        $request    = Request::create('/');
        $request->attributes->set('lang', $lang);

        return $request;
    }

    private function toArray(BlogCategory $category, string $langCode): array
    {
        $request  = $this->makeRequest($langCode);
        $resource = new BlogCategoryResource($category);

        return $resource->toArray($request);
    }
}
