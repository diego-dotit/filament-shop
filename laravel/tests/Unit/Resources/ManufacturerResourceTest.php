<?php

namespace Tests\Unit\Resources;

use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Slug\Models\Slug;
use App\Http\Resources\Api\Manufacturer\ManufacturerResource;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ManufacturerResourceTest extends TestCase
{
    public function test_manufacturer_resource_has_expected_keys(): void
    {
        $manufacturer = $this->makeManufacturer();

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('meta_title', $data);
        $this->assertArrayHasKey('meta_description', $data);
        $this->assertArrayHasKey('meta_keywords', $data);
    }

    public function test_manufacturer_resource_uses_fallback_locale_for_name(): void
    {
        $manufacturer = $this->makeManufacturer();

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('Acme Corp', $data['name']);
    }

    public function test_manufacturer_resource_uses_request_lang_for_translation(): void
    {
        $manufacturer = $this->makeManufacturer();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray($request);

        $this->assertSame('Acme Corp FR', $data['name']);
    }

    public function test_manufacturer_resource_includes_description(): void
    {
        $manufacturer = $this->makeManufacturerWithMetaFields();

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('A great manufacturer', $data['description']);
    }

    public function test_manufacturer_resource_includes_meta_title(): void
    {
        $manufacturer = $this->makeManufacturerWithMetaFields();

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('Acme Meta Title', $data['meta_title']);
    }

    public function test_manufacturer_resource_includes_meta_description(): void
    {
        $manufacturer = $this->makeManufacturerWithMetaFields();

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('Acme meta description', $data['meta_description']);
    }

    public function test_manufacturer_resource_includes_meta_keywords(): void
    {
        $manufacturer = $this->makeManufacturerWithMetaFields();

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('acme, manufacturer', $data['meta_keywords']);
    }

    public function test_manufacturer_resource_meta_fields_respect_locale(): void
    {
        $manufacturer = $this->makeManufacturerWithMetaFields();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray($request);

        $this->assertSame('Un super fabricant', $data['description']);
        $this->assertSame('Titre méta Acme', $data['meta_title']);
        $this->assertSame('Description méta Acme', $data['meta_description']);
        $this->assertSame('acme, fabricant', $data['meta_keywords']);
    }

    public function test_manufacturer_resource_uses_locale_specific_slug_when_available(): void
    {
        $localeSlug = new Slug();
        $localeSlug->setRawAttributes(['slug' => 'acme-corp-fr', 'locale' => 'fr']);

        /** @var Manufacturer&\Mockery\MockInterface $manufacturer */
        $manufacturer = Mockery::mock(Manufacturer::class)->makePartial();
        $manufacturer->setRawAttributes([
            'id'   => 1,
            'slug' => 'acme-corp',
            'name' => json_encode(['en' => 'Acme Corp', 'fr' => 'Acme Corp FR']),
        ]);
        $manufacturer->exists = true;
        $manufacturer->shouldReceive('getSlugForLocale')->with('fr')->andReturn($localeSlug);
        $manufacturer->shouldReceive('getSlugForLocale')->with('en')->andReturn(null);

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray($request);

        $this->assertSame('acme-corp-fr', $data['slug']);
    }

    public function test_manufacturer_resource_falls_back_to_canonical_slug_when_no_locale_slug(): void
    {
        /** @var Manufacturer&\Mockery\MockInterface $manufacturer */
        $manufacturer = Mockery::mock(Manufacturer::class)->makePartial();
        $manufacturer->setRawAttributes([
            'id'   => 1,
            'slug' => 'acme-corp',
            'name' => json_encode(['en' => 'Acme Corp', 'fr' => 'Acme Corp FR']),
        ]);
        $manufacturer->exists = true;
        $manufacturer->shouldReceive('getSlugForLocale')->with('en')->andReturn(null);

        $resource = new ManufacturerResource($manufacturer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame('acme-corp', $data['slug']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeManufacturer(): Manufacturer
    {
        $manufacturer = new Manufacturer();
        $manufacturer->setRawAttributes([
            'id'   => 1,
            'slug' => 'acme-corp',
            'name' => json_encode(['en' => 'Acme Corp', 'fr' => 'Acme Corp FR']),
        ]);

        return $manufacturer;
    }

    private function makeManufacturerWithMetaFields(): Manufacturer
    {
        $manufacturer = new Manufacturer();
        $manufacturer->setRawAttributes([
            'id'               => 1,
            'slug'             => 'acme-corp',
            'name'             => json_encode(['en' => 'Acme Corp', 'fr' => 'Acme Corp FR']),
            'description'      => json_encode(['en' => 'A great manufacturer', 'fr' => 'Un super fabricant']),
            'meta_title'       => json_encode(['en' => 'Acme Meta Title', 'fr' => 'Titre méta Acme']),
            'meta_description' => json_encode(['en' => 'Acme meta description', 'fr' => 'Description méta Acme']),
            'meta_keywords'    => json_encode(['en' => 'acme, manufacturer', 'fr' => 'acme, fabricant']),
        ]);

        return $manufacturer;
    }
}
