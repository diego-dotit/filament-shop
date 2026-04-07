<?php

namespace Tests\Unit\Resources;

use App\Domains\Page\Models\Page;
use App\Http\Resources\Api\Page\PageResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    public function test_page_resource_has_all_required_keys(): void
    {
        $page = $this->makePage();

        $resource = new PageResource($page);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('meta_title', $data);
        $this->assertArrayHasKey('meta_description', $data);
        $this->assertArrayHasKey('meta_keywords', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_page_resource_has_no_media_fields(): void
    {
        $page = $this->makePage();

        $resource = new PageResource($page);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayNotHasKey('thumbnail_url', $data);
        $this->assertArrayNotHasKey('image', $data);
        $this->assertArrayNotHasKey('media', $data);
    }

    public function test_page_resource_resolves_translatable_fields_with_fallback_locale(): void
    {
        $page = $this->makePage();

        $resource = new PageResource($page);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('About Us', $data['title']);
        $this->assertSame('Our company description', $data['description']);
        $this->assertSame('About Us Meta', $data['meta_title']);
        $this->assertSame('Meta description text', $data['meta_description']);
        $this->assertSame('about, company', $data['meta_keywords']);
    }

    public function test_page_resource_resolves_translatable_fields_with_request_lang(): void
    {
        $page = $this->makePage();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new PageResource($page);
        $data = $resource->toArray($request);

        $this->assertSame('À propos', $data['title']);
        $this->assertSame('Description de notre entreprise', $data['description']);
    }

    public function test_page_resource_returns_status_as_string(): void
    {
        $page = $this->makePage();

        $resource = new PageResource($page);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('active', $data['status']);
        $this->assertIsString($data['status']);
    }

    public function test_page_resource_slug_falls_back_to_model_slug(): void
    {
        $page = $this->makePage();

        $resource = new PageResource($page);
        $data = $resource->toArray(Request::create('/'));

        // Model is not persisted (exists=false) so falls back to $this->slug
        $this->assertSame('about-us', $data['slug']);
    }

    public function test_page_resource_timestamps_are_iso_strings_or_null(): void
    {
        $page = $this->makePage();

        $resource = new PageResource($page);
        $data = $resource->toArray(Request::create('/'));

        // Non-persisted model has null timestamps
        $this->assertNull($data['created_at']);
        $this->assertNull($data['updated_at']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makePage(): Page
    {
        $page = new Page();
        $page->setRawAttributes([
            'id'               => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            'slug'             => 'about-us',
            'title'            => json_encode(['en' => 'About Us', 'fr' => 'À propos']),
            'description'      => json_encode(['en' => 'Our company description', 'fr' => 'Description de notre entreprise']),
            'meta_title'       => json_encode(['en' => 'About Us Meta', 'fr' => 'Méta à propos']),
            'meta_description' => json_encode(['en' => 'Meta description text', 'fr' => 'Texte de méta description']),
            'meta_keywords'    => json_encode(['en' => 'about, company', 'fr' => 'à propos, entreprise']),
            'status'           => 'active',
        ]);

        return $page;
    }
}
