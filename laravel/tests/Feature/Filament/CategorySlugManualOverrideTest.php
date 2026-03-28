<?php

namespace Tests\Feature\Filament;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Slug\Models\Slug;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * T1.9 – Manual override capability for slug fields in CategoryResource.
 *
 * Covers:
 *  - alphaDash validation on slug_{code} inputs
 *  - Uniqueness validation against the slugs table (not categories table)
 *  - Persistence of slug_{code} form values to the slugs table on create
 *  - Persistence of slug_{code} form values to the slugs table on edit
 *  - Saving a record with an unchanged slug does not trigger a uniqueness error
 */
class CategorySlugManualOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        Language::factory()->create(['code' => 'en', 'is_default' => true, 'name' => 'English']);
    }

    // ── alphaDash validation ────────────────────────────────────────────────

    public function test_slug_field_rejects_value_with_spaces(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'My Category'],
                'slug_en' => 'invalid slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_slug_field_rejects_value_with_special_characters(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'My Category'],
                'slug_en' => 'slug!@#$',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_slug_field_accepts_valid_alphadash_value(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'My Category'],
                'slug_en' => 'valid-slug-123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    // ── Uniqueness against slugs table ─────────────────────────────────────

    public function test_create_fails_when_slug_already_exists_in_slugs_table(): void
    {
        Slug::create([
            'sluggable_type' => Category::class,
            'sluggable_id'   => 9999,
            'locale'         => 'en',
            'slug'           => 'taken-slug',
        ]);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'New Category'],
                'slug_en' => 'taken-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_edit_fails_when_slug_is_used_by_another_category(): void
    {
        $categoryA = Category::factory()->active()->create([
            'name' => ['en' => 'Category A'],
            'slug' => 'category-a',
        ]);
        // Override HasSlugs-auto-created slug to our known value
        $categoryA->slugs()->where('locale', 'en')->update(['slug' => 'category-a']);

        $categoryB = Category::factory()->active()->create([
            'name' => ['en' => 'Category B'],
            'slug' => 'category-b',
        ]);

        Livewire::test(EditCategory::class, ['record' => $categoryB->getRouteKey()])
            ->fillForm([
                'name'    => ['en' => 'Category B'],
                'slug_en' => 'category-a',
            ])
            ->call('save')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── Create persistence ─────────────────────────────────────────────────

    public function test_creating_category_persists_slug_en_to_slugs_table(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'New Category'],
                'slug_en' => 'new-category-custom',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'new-category-custom')->first();
        $this->assertNotNull($category, 'Category should be created with the correct slug');

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'new-category-custom',
        ]);
    }

    public function test_creating_category_persists_manual_override_not_auto_generated_slug(): void
    {
        // Name would auto-generate 'my-category', but we manually set 'manual-override'
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'    => ['en' => 'My Category'],
                'slug_en' => 'manual-override',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'manual-override')->first();
        $this->assertNotNull($category);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'slug'           => 'manual-override',
        ]);

        $this->assertDatabaseMissing('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'slug'           => 'my-category',
        ]);
    }

    // ── Edit persistence ───────────────────────────────────────────────────

    public function test_editing_category_updates_slug_in_slugs_table(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Original Category'],
            'slug' => 'original-slug',
        ]);
        $category->slugs()->where('locale', 'en')->update(['slug' => 'original-slug']);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name'    => ['en' => 'Original Category'],
                'slug_en' => 'updated-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'updated-slug',
        ]);

        $this->assertDatabaseMissing('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'slug'           => 'original-slug',
        ]);
    }

    public function test_edit_allows_saving_with_unchanged_slug(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Stable Category'],
            'slug' => 'stable-slug',
        ]);
        $category->slugs()->where('locale', 'en')->update(['slug' => 'stable-slug']);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name'    => ['en' => 'Stable Category'],
                'slug_en' => 'stable-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
