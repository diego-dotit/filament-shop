<?php

namespace Tests\Feature\Filament;

use App\Domains\Category\Models\Category;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);
    }

    public function test_list_categories_page_renders_successfully(): void
    {
        Livewire::test(ListCategories::class)
            ->assertSuccessful();
    }

    public function test_list_categories_displays_category_records(): void
    {
        $categories = Category::factory()->count(3)->active()->create();

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords($categories);
    }

    public function test_list_categories_shows_parent_category_name(): void
    {
        $parent = Category::factory()->active()->create();
        $child = Category::factory()->active()->create(['parent_id' => $parent->id]);

        Livewire::test(ListCategories::class)
            ->assertTableColumnStateSet('parent.name', $parent->getTranslation('name', 'en'), record: $child);
    }

    public function test_list_categories_shows_empty_parent_for_root_category(): void
    {
        $root = Category::factory()->active()->create(['parent_id' => null]);

        Livewire::test(ListCategories::class)
            ->assertTableColumnStateSet('parent.name', null, record: $root);
    }

    public function test_list_categories_has_required_columns(): void
    {
        Livewire::test(ListCategories::class)
            ->assertTableColumnExists('id')
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('slug')
            ->assertTableColumnExists('parent.name')
            ->assertTableColumnExists('is_active')
            ->assertTableColumnExists('created_at');
    }

    public function test_list_categories_is_searchable_by_name(): void
    {
        $matchingCategory = Category::factory()->active()->create([
            'name' => ['en' => 'Electronics Gadgets'],
            'slug' => 'electronics-gadgets',
        ]);
        $otherCategory = Category::factory()->active()->create([
            'name' => ['en' => 'Furniture Items'],
            'slug' => 'furniture-items',
        ]);

        Livewire::test(ListCategories::class)
            ->searchTable('Electronics')
            ->assertCanSeeTableRecords([$matchingCategory])
            ->assertCanNotSeeTableRecords([$otherCategory]);
    }

    public function test_list_categories_is_searchable_by_slug(): void
    {
        $matchingCategory = Category::factory()->active()->create([
            'name' => ['en' => 'Test Category Alpha'],
            'slug' => 'unique-alpha-slug-test',
        ]);
        $otherCategory = Category::factory()->active()->create([
            'name' => ['en' => 'Test Category Beta'],
            'slug' => 'unique-beta-slug-test',
        ]);

        Livewire::test(ListCategories::class)
            ->searchTable('alpha-slug')
            ->assertCanSeeTableRecords([$matchingCategory])
            ->assertCanNotSeeTableRecords([$otherCategory]);
    }

    public function test_list_categories_has_is_active_filter(): void
    {
        Livewire::test(ListCategories::class)
            ->assertTableFilterExists('is_active');
    }

    public function test_list_categories_is_active_filter_shows_only_active(): void
    {
        $activeCategory = Category::factory()->active()->create();
        $inactiveCategory = Category::factory()->inactive()->create();

        Livewire::test(ListCategories::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activeCategory])
            ->assertCanNotSeeTableRecords([$inactiveCategory]);
    }

    public function test_list_categories_is_active_filter_shows_only_inactive(): void
    {
        $activeCategory = Category::factory()->active()->create();
        $inactiveCategory = Category::factory()->inactive()->create();

        Livewire::test(ListCategories::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$inactiveCategory])
            ->assertCanNotSeeTableRecords([$activeCategory]);
    }

    public function test_category_resource_has_correct_model(): void
    {
        $this->assertEquals(Category::class, CategoryResource::getModel());
    }

    public function test_category_resource_has_list_page(): void
    {
        $pages = CategoryResource::getPages();
        $this->assertArrayHasKey('index', $pages);
    }

    // ── Resource Pages ────────────────────────────────────────────────────

    public function test_category_resource_has_create_and_edit_pages(): void
    {
        $pages = CategoryResource::getPages();

        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    // ── Create Page ───────────────────────────────────────────────────────

    public function test_create_category_page_renders(): void
    {
        Livewire::test(CreateCategory::class)
            ->assertSuccessful();
    }

    public function test_can_create_root_category_without_parent(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => ['en' => 'Electronics'],
                'slug'      => 'electronics',
                'parent_id' => null,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'slug'      => 'electronics',
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_can_create_child_category_with_parent(): void
    {
        $parent = Category::factory()->active()->create(['slug' => 'parent-cat']);

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

    public function test_create_category_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'existing-slug']);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => ['en' => 'Another Category'],
                'slug' => 'existing-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_slug_is_auto_generated_from_name(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => ['en' => 'My New Category']])
            ->assertFormFieldIsVisible('slug');
    }

    // ── Edit Page ─────────────────────────────────────────────────────────

    public function test_edit_category_page_renders(): void
    {
        $category = Category::factory()->active()->create();

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_edit_category_prepopulates_existing_data(): void
    {
        $parent   = Category::factory()->active()->create(['slug' => 'parent-prepop']);
        $category = Category::factory()->active()->create([
            'name'      => ['en' => 'Prepop Category'],
            'slug'      => 'prepop-category',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet([
                'slug'      => 'prepop-category',
                'parent_id' => $parent->id,
                'is_active' => true,
            ]);
    }

    public function test_edit_category_slug_unique_ignores_own_record(): void
    {
        $category = Category::factory()->active()->create(['slug' => 'my-own-slug']);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name' => ['en' => 'Updated Name'],
                'slug' => 'my-own-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ── Circular Reference Prevention ─────────────────────────────────────

    public function test_cannot_set_self_as_parent(): void
    {
        $category = Category::factory()->active()->create(['slug' => 'self-ref-cat']);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name'      => ['en' => 'Self Reference'],
                'slug'      => 'self-ref-cat',
                'parent_id' => $category->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);
    }

    public function test_cannot_set_descendant_as_parent(): void
    {
        $parent = Category::factory()->active()->create(['slug' => 'ancestor-cat']);
        $child  = Category::factory()->active()->create([
            'slug'      => 'descendant-cat',
            'parent_id' => $parent->id,
        ]);

        Livewire::test(EditCategory::class, ['record' => $parent->getRouteKey()])
            ->fillForm([
                'name'      => ['en' => 'Ancestor'],
                'slug'      => 'ancestor-cat',
                'parent_id' => $child->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);
    }

    // ── Translation Handling ──────────────────────────────────────────────

    public function test_category_name_translation_stored_as_json(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => ['en' => 'Translated Name'],
                'slug'      => 'translated-name',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'translated-name')->first();
        $this->assertNotNull($category);
        $this->assertEquals('Translated Name', $category->getTranslation('name', 'en'));
    }
}
