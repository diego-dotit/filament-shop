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

class CategorySlugPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        // Create a default language so HasSlugs and the form work consistently
        Language::factory()->create(['code' => 'en', 'is_default' => true, 'name' => 'English']);
    }

    // ── Create persistence ─────────────────────────────────────────────────

    public function test_creating_category_via_form_persists_slug_to_slugs_table(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => ['en' => 'My Category'],
                'slug_en'   => 'my-custom-slug',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'my-custom-slug')->first();
        $this->assertNotNull($category);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'slug'           => 'my-custom-slug',
        ]);
    }

    public function test_creating_category_persists_manual_slug_not_name_derived_slug(): void
    {
        // The name would auto-generate 'my-category' but we manually set 'custom-override'
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => ['en' => 'My Category'],
                'slug_en'   => 'custom-override',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::where('slug', 'custom-override')->first();
        $this->assertNotNull($category);

        // The slug in slugs table should be the manually-entered one, not 'my-category'
        $slug = Slug::where('sluggable_type', Category::class)
            ->where('sluggable_id', $category->id)
            ->where('slug', 'custom-override')
            ->first();

        $this->assertNotNull($slug, 'Slugs table should contain the manually-entered slug');

        // The auto-derived 'my-category' should NOT exist for this category+locale
        $wrongSlug = Slug::where('sluggable_type', Category::class)
            ->where('sluggable_id', $category->id)
            ->where('slug', 'my-category')
            ->first();

        $this->assertNull($wrongSlug, 'Slugs table should not have the name-derived slug for this locale when manually overridden');
    }

    // ── Edit persistence ───────────────────────────────────────────────────

    public function test_editing_category_via_form_updates_slug_in_slugs_table(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Original Name'],
            'slug' => 'original-slug',
        ]);

        // HasSlugs created 'original-name' from the name. Override it to our test value.
        $category->slugs()->where('locale', 'en')->update(['slug' => 'original-slug']);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name'    => ['en' => 'Original Name'],
                'slug_en' => 'updated-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'slug'           => 'updated-slug',
        ]);

        $this->assertDatabaseMissing('slugs', [
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'slug'           => 'original-slug',
        ]);
    }

    // ── Uniqueness validation against slugs table ──────────────────────────

    public function test_create_fails_when_slug_already_exists_in_slugs_table(): void
    {
        // Directly insert a slug in the slugs table to simulate a taken slug
        // (regardless of model association, the slug must be globally unique)
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

    public function test_edit_allows_saving_with_unchanged_slug(): void
    {
        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Stable Category'],
            'slug' => 'stable-slug',
        ]);

        // HasSlugs created a slug from name. Override it to our test value.
        $category->slugs()->where('locale', 'en')->update(['slug' => 'stable-slug']);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'name'    => ['en' => 'Stable Category'],
                'slug_en' => 'stable-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug_en']);
    }
}
