<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CurrencyResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\LanguageResource;
use App\Filament\Resources\ManufacturerResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ReviewResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
    }

    public function test_customers_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(CustomerResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_products_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(ProductResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_categories_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(CategoryResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_manufacturers_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(ManufacturerResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_orders_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(OrderResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_reviews_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(ReviewResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_languages_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(LanguageResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_currencies_list_page_is_accessible_to_authenticated_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(CurrencyResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_all_resources_have_navigation_groups_defined(): void
    {
        $this->assertNotEmpty(CustomerResource::getNavigationGroup());
        $this->assertNotEmpty(ProductResource::getNavigationGroup());
        $this->assertNotEmpty(CategoryResource::getNavigationGroup());
        $this->assertNotEmpty(ManufacturerResource::getNavigationGroup());
        $this->assertNotEmpty(OrderResource::getNavigationGroup());
        $this->assertNotEmpty(ReviewResource::getNavigationGroup());
        $this->assertNotEmpty(LanguageResource::getNavigationGroup());
        $this->assertNotEmpty(CurrencyResource::getNavigationGroup());
    }

    public function test_catalog_resources_share_catalog_navigation_group(): void
    {
        $this->assertSame('Catalog', ProductResource::getNavigationGroup());
        $this->assertSame('Catalog', CategoryResource::getNavigationGroup());
        $this->assertSame('Catalog', ManufacturerResource::getNavigationGroup());
    }

    public function test_order_resources_share_orders_navigation_group(): void
    {
        $this->assertSame('Orders', OrderResource::getNavigationGroup());
        $this->assertSame('Orders', ReviewResource::getNavigationGroup());
    }

    public function test_configuration_resources_share_configuration_navigation_group(): void
    {
        $this->assertSame('Configuration', LanguageResource::getNavigationGroup());
        $this->assertSame('Configuration', CurrencyResource::getNavigationGroup());
    }

    public function test_customers_are_in_customers_navigation_group(): void
    {
        $this->assertSame('Customers', CustomerResource::getNavigationGroup());
    }

    public function test_unauthenticated_user_is_redirected_from_admin_panel(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
    }
}
