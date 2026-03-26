<?php

namespace Tests\Unit\Models;

use App\Domains\Category\Models\Category;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
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

    public function test_category_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, new Category());
    }

    public function test_fillable_contains_expected_fields(): void
    {
        $category = new Category();

        $this->assertSame(
            ['parent_id', 'name', 'slug', 'is_active'],
            $category->getFillable()
        );
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $category = new Category();

        $this->assertArrayHasKey('is_active', $category->getCasts());
        $this->assertSame('boolean', $category->getCasts()['is_active']);
    }

    public function test_parent_relationship_is_belongs_to_category(): void
    {
        $category = new Category();
        $relation = $category->parent();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
        $this->assertInstanceOf(Category::class, $relation->getRelated());
    }

    public function test_children_relationship_is_has_many_category(): void
    {
        $category = new Category();
        $relation = $category->children();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
        $this->assertInstanceOf(Category::class, $relation->getRelated());
    }

    public function test_products_relationship_is_belongs_to_many_product(): void
    {
        $category = new Category();
        $relation = $category->products();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $relation);
        $this->assertInstanceOf(Product::class, $relation->getRelated());
    }

    public function test_products_relationship_uses_correct_pivot_table(): void
    {
        $category = new Category();
        $relation = $category->products();

        $this->assertSame('category_product', $relation->getTable());
    }

    // ── Translatable ─────────────────────────────────────────────────────────

    public function test_category_uses_has_translations_trait(): void
    {
        $traits = class_uses_recursive(Category::class);

        $this->assertContains(\Spatie\Translatable\HasTranslations::class, $traits);
    }

    public function test_category_translatable_field_is_name(): void
    {
        $category = new Category();

        $this->assertSame(['name'], $category->getTranslatableAttributes());
    }

    // ── Media Library ────────────────────────────────────────────────────────

    public function test_category_implements_has_media_interface(): void
    {
        $this->assertInstanceOf(\Spatie\MediaLibrary\HasMedia::class, new Category());
    }

    public function test_category_uses_interacts_with_media_trait(): void
    {
        $traits = class_uses_recursive(Category::class);

        $this->assertContains(\Spatie\MediaLibrary\InteractsWithMedia::class, $traits);
    }

    public function test_category_registers_thumbnail_media_collection(): void
    {
        $category = new Category();
        $category->registerMediaCollections();
        $collections = $category->getRegisteredMediaCollections();

        $names = $collections->pluck('name')->all();
        $this->assertContains('thumbnail', $names);
    }
}
