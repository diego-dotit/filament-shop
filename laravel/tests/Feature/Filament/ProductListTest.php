<?php

namespace Tests\Feature\Filament;

use App\Domains\Product\Models\Product;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_list_products_page_renders_successfully(): void
    {
        Product::factory()->count(3)->create();

        Livewire::test(ListProducts::class)
            ->assertSuccessful();
    }

    public function test_list_products_shows_products_in_table(): void
    {
        $products = Product::factory()->count(3)->create();

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords($products);
    }

    public function test_list_products_filters_by_active_status_true(): void
    {
        $activeProducts = Product::factory()->active()->count(3)->create();
        $inactiveProducts = Product::factory()->inactive()->count(2)->create();

        Livewire::test(ListProducts::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords($activeProducts)
            ->assertCanNotSeeTableRecords($inactiveProducts);
    }

    public function test_list_products_filters_by_active_status_false(): void
    {
        $activeProducts = Product::factory()->active()->count(3)->create();
        $inactiveProducts = Product::factory()->inactive()->count(2)->create();

        Livewire::test(ListProducts::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords($inactiveProducts)
            ->assertCanNotSeeTableRecords($activeProducts);
    }

    public function test_list_products_is_searchable_by_slug(): void
    {
        $products = Product::factory()->count(5)->create();

        $targetProduct = $products->first();
        $slug = $targetProduct->slug;

        $otherProducts = $products->skip(1)->values();

        Livewire::test(ListProducts::class)
            ->searchTable($slug)
            ->assertCanSeeTableRecords([$targetProduct])
            ->assertCanNotSeeTableRecords($otherProducts);
    }

    public function test_list_products_is_searchable_by_name(): void
    {
        // Use a distinctive name that won't collide with others
        $uniqueProduct = Product::factory()->create([
            'name' => ['en' => 'UniqueTestProductName99999'],
            'slug' => 'unique-test-product-name-99999',
        ]);

        $otherProducts = Product::factory()->count(3)->create();

        Livewire::test(ListProducts::class)
            ->searchTable('UniqueTestProductName99999')
            ->assertCanSeeTableRecords([$uniqueProduct])
            ->assertCanNotSeeTableRecords($otherProducts);
    }

    public function test_list_products_table_has_is_active_filter(): void
    {
        Livewire::test(ListProducts::class)
            ->assertTableFilterExists('is_active');
    }

    public function test_list_products_paginates_at_ten_records(): void
    {
        Product::factory()->count(15)->create();

        Livewire::test(ListProducts::class)
            ->assertSet('tableRecordsPerPage', 10);
    }
}
