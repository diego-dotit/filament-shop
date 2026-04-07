<?php

namespace Tests\Unit\Resources;

use App\Domains\Category\Models\Category;
use App\Http\Resources\Api\Category\CategoryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_resource_has_expected_keys(): void
    {
        $category = $this->makeCategory();

        $resource = new CategoryResource($category);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('is_active', $data);
        $this->assertArrayHasKey('children', $data);
        $this->assertArrayHasKey('image', $data);
    }

    public function test_category_resource_uses_fallback_locale_for_name(): void
    {
        $category = $this->makeCategory();

        $resource = new CategoryResource($category);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('Electronics', $data['name']);
    }

    public function test_category_resource_uses_request_lang_for_translation(): void
    {
        $category = $this->makeCategory();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new CategoryResource($category);
        $data = $resource->toArray($request);

        $this->assertSame('Électronique', $data['name']);
    }

    public function test_category_resource_slug_maps_correctly(): void
    {
        $category = $this->makeCategory();

        $resource = new CategoryResource($category);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('electronics', $data['slug']);
        $this->assertTrue($data['is_active']);
    }

    public function test_category_resource_includes_description_field(): void
    {
        $category = $this->makeCategoryWithMetaFields();

        $resource = new CategoryResource($category);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('description', $data);
        $this->assertSame('Electronics description', $data['description']);
    }

    public function test_category_resource_includes_meta_title_field(): void
    {
        $category = $this->makeCategoryWithMetaFields();

        $resource = new CategoryResource($category);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('meta_title', $data);
        $this->assertSame('Electronics Meta Title', $data['meta_title']);
    }

    public function test_category_resource_includes_meta_description_field(): void
    {
        $category = $this->makeCategoryWithMetaFields();

        $resource = new CategoryResource($category);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('meta_description', $data);
        $this->assertSame('Electronics meta description', $data['meta_description']);
    }

    public function test_category_resource_includes_meta_keywords_field(): void
    {
        $category = $this->makeCategoryWithMetaFields();

        $resource = new CategoryResource($category);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('meta_keywords', $data);
        $this->assertSame('electronics, gadgets', $data['meta_keywords']);
    }

    public function test_category_resource_meta_fields_respect_locale(): void
    {
        $category = $this->makeCategoryWithMetaFields();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new CategoryResource($category);
        $data = $resource->toArray($request);

        $this->assertSame('Description électronique', $data['description']);
        $this->assertSame('Titre méta électronique', $data['meta_title']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeCategory(): Category
    {
        $category = new Category();
        $category->setRawAttributes([
            'id'        => 1,
            'slug'      => 'electronics',
            'name'      => json_encode(['en' => 'Electronics', 'fr' => 'Électronique']),
            'is_active' => true,
        ]);

        return $category;
    }

    private function makeCategoryWithMetaFields(): Category
    {
        $category = new Category();
        $category->setRawAttributes([
            'id'               => 1,
            'slug'             => 'electronics',
            'name'             => json_encode(['en' => 'Electronics', 'fr' => 'Électronique']),
            'is_active'        => true,
            'description'      => json_encode(['en' => 'Electronics description', 'fr' => 'Description électronique']),
            'meta_title'       => json_encode(['en' => 'Electronics Meta Title', 'fr' => 'Titre méta électronique']),
            'meta_description' => json_encode(['en' => 'Electronics meta description', 'fr' => 'Description méta électronique']),
            'meta_keywords'    => json_encode(['en' => 'electronics, gadgets', 'fr' => 'électronique, gadgets']),
        ]);

        return $category;
    }
}
