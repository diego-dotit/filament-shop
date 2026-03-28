<?php

namespace Tests\Feature\Filament;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource;
use App\Models\User;
use App\Rules\NoCircularCategoryReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryCreateEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Resource Configuration ─────────────────────────────────────────────

    public function test_category_resource_has_create_and_edit_pages(): void
    {
        $pages = CategoryResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    // ── Create Page ────────────────────────────────────────────────────────

    public function test_create_category_page_renders(): void
    {
        Livewire::test(CreateCategory::class)
            ->assertSuccessful();
    }

    public function test_can_create_root_category_without_parent(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => ['en' => 'Root Category'],
                'slug'      => 'root-category',
                'parent_id' => null,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'slug'      => 'root-category',
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_can_create_category_with_parent(): void
    {
        $parent = Category::factory()->active()->create([
            'name' => ['en' => 'Parent Category'],
            'slug' => 'parent-category',
        ]);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => ['en' => 'Child Category'],
                'slug'      => 'child-category',
                'parent_id' => $parent->id,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'slug'      => 'child-category',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_create_category_requires_name(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => ['en' => ''],
                'slug' => 'some-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['name.en']);
    }

    public function test_create_category_requires_unique_slug(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $existing = Category::factory()->create([
            'name' => ['en' => 'Existing Category'],
            'slug' => 'existing-slug',
        ]);
        $existing->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'existing-slug'],
        );

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'New Category'],
                'slug_en' => 'existing-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_create_form_has_required_fields(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateCategory::class)
            ->assertFormFieldExists('name.en')
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('parent_id')
            ->assertFormFieldExists('is_active');
    }

    // ── Edit Page ──────────────────────────────────────────────────────────

    public function test_edit_category_page_renders_with_existing_data(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Test Category'],
            'slug' => 'test-category',
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['slug' => 'test-category']);
    }

    public function test_edit_category_pre_populates_parent(): void
    {
        $parent = Category::factory()->active()->create([
            'name' => ['en' => 'Parent Category'],
            'slug' => 'parent-cat',
        ]);
        $child = Category::factory()->active()->create([
            'name'      => ['en' => 'Child Category'],
            'slug'      => 'child-cat',
            'parent_id' => $parent->id,
        ]);

        Livewire::test(EditCategory::class, ['record' => $child->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['parent_id' => $parent->id]);
    }

    public function test_edit_category_allows_own_slug_without_unique_error(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'My Category'],
            'slug' => 'my-category-slug',
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => ['en' => 'My Category'],
                'slug' => 'my-category-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug']);
    }

    // ── Circular Reference Prevention ──────────────────────────────────────

    public function test_cannot_set_self_as_parent(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Self Category'],
            'slug' => 'self-category',
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name'      => ['en' => 'Self Category'],
                'slug'      => 'self-category',
                'parent_id' => $category->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);
    }

    public function test_cannot_set_descendant_as_parent(): void
    {
        $parent = Category::factory()->active()->create([
            'name' => ['en' => 'Grandparent'],
            'slug' => 'grandparent',
        ]);
        $child = Category::factory()->active()->create([
            'name'      => ['en' => 'Child'],
            'slug'      => 'child-descendant',
            'parent_id' => $parent->id,
        ]);

        // Try to set parent's parent to child (would create a cycle)
        Livewire::test(EditCategory::class, ['record' => $parent->getRouteKey()])
            ->fillForm([
                'name'      => ['en' => 'Grandparent'],
                'slug'      => 'grandparent',
                'parent_id' => $child->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);
    }

    // ── NoCircularCategoryReference Rule (Unit) ────────────────────────────

    public function test_no_circular_rule_passes_for_null_parent(): void
    {
        $category = Category::factory()->active()->create();

        $rule = new NoCircularCategoryReference($category->id);

        $passed = true;
        $rule->validate('parent_id', null, function () use (&$passed) {
            $passed = false;
        });

        $this->assertTrue($passed, 'Rule should pass for null parent_id');
    }

    public function test_no_circular_rule_fails_for_self(): void
    {
        $category = Category::factory()->active()->create();

        $rule = new NoCircularCategoryReference($category->id);

        $failed = false;
        $rule->validate('parent_id', $category->id, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Rule should fail when parent_id equals the category id');
    }

    public function test_no_circular_rule_fails_for_descendant(): void
    {
        $parent = Category::factory()->active()->create();
        $child  = Category::factory()->active()->create(['parent_id' => $parent->id]);

        $rule = new NoCircularCategoryReference($parent->id);

        $failed = false;
        $rule->validate('parent_id', $child->id, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed, 'Rule should fail when parent_id is a descendant');
    }

    public function test_no_circular_rule_passes_for_valid_parent(): void
    {
        $catA = Category::factory()->active()->create();
        $catB = Category::factory()->active()->create();

        $rule = new NoCircularCategoryReference($catA->id);

        $passed = true;
        $rule->validate('parent_id', $catB->id, function () use (&$passed) {
            $passed = false;
        });

        $this->assertTrue($passed, 'Rule should pass when parent_id is an unrelated category');
    }
}
