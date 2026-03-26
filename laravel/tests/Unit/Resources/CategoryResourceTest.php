<?php

namespace Tests\Unit\Resources;

use App\Domains\Category\Models\Category;
use App\Http\Resources\Api\Category\CategoryResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
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
}
