<?php

namespace Tests\Unit\Models;

use App\Domains\Slug\Models\Slug;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use PHPUnit\Framework\TestCase;

class SlugTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    public function test_slug_class_exists(): void
    {
        $this->assertTrue(class_exists(Slug::class));
    }

    public function test_slug_can_be_instantiated(): void
    {
        $slug = new Slug();

        $this->assertInstanceOf(Slug::class, $slug);
    }

    public function test_slug_fillable_contains_expected_fields(): void
    {
        $slug = new Slug();

        $this->assertSame(
            ['sluggable_type', 'sluggable_id', 'locale', 'slug'],
            $slug->getFillable()
        );
    }

    public function test_slug_attribute_assignment(): void
    {
        $slug         = new Slug();
        $slug->locale = 'en';
        $slug->slug   = 'my-product';

        $this->assertSame('en', $slug->locale);
        $this->assertSame('my-product', $slug->slug);
    }

    public function test_slug_sluggable_relationship_is_morph_to(): void
    {
        $slug     = new Slug();
        $relation = $slug->sluggable();

        $this->assertInstanceOf(MorphTo::class, $relation);
    }

    public function test_slug_sluggable_relationship_uses_correct_morph_name(): void
    {
        $slug     = new Slug();
        $relation = $slug->sluggable();

        // getMorphType() returns the morph type column name (e.g. 'sluggable_type')
        $this->assertSame('sluggable_type', $relation->getMorphType());
    }
}
