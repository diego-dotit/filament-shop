<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * T3.17 — Verify all API routes are correctly registered and middleware applied.
 *
 * Checks:
 *  - All expected endpoints exist under the /api prefix
 *  - Public endpoints return a response without authentication
 *  - Protected endpoints return 401 without a token
 *  - Protected endpoints return a successful response with a valid Sanctum token
 *  - The ResolveLanguageAndCurrency middleware is applied to the api middleware group
 */
class RouteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Route-list assertions (structural)
    // -----------------------------------------------------------------------

    /**
     * @dataProvider publicRouteProvider
     */
    public function test_public_routes_are_registered(string $method, string $uri): void
    {
        $this->assertRouteRegistered($method, $uri);
    }

    public static function publicRouteProvider(): array
    {
        return [
            // Auth
            'register'              => ['POST', 'api/auth/register'],
            'login'                 => ['POST', 'api/auth/login'],
            // Categories
            'categories.index'      => ['GET',  'api/categories'],
            'categories.show'       => ['GET',  'api/categories/{slug}'],
            // Products
            'products.index'        => ['GET',  'api/products'],
            'products.show'         => ['GET',  'api/products/{slug}'],
            // Reviews list (public)
            'reviews.index'         => ['GET',  'api/products/{productId}/reviews'],
        ];
    }

    /**
     * @dataProvider protectedRouteProvider
     */
    public function test_protected_routes_are_registered(string $method, string $uri): void
    {
        $this->assertRouteRegistered($method, $uri);
    }

    public static function protectedRouteProvider(): array
    {
        return [
            // Auth (me)
            'auth.me'                           => ['GET',    'api/auth/me'],
            // Customer profile & addresses
            'customers.me.show'                 => ['GET',    'api/customers/me'],
            'customers.me.update'               => ['PUT',    'api/customers/me'],
            'customers.me.addresses.index'      => ['GET',    'api/customers/me/addresses'],
            'customers.me.addresses.store'      => ['POST',   'api/customers/me/addresses'],
            'customers.me.addresses.update'     => ['PUT',    'api/customers/me/addresses/{address}'],
            'customers.me.addresses.destroy'    => ['DELETE', 'api/customers/me/addresses/{address}'],
            // Cart
            'cart.show'                         => ['GET',    'api/cart'],
            'cart.items.store'                  => ['POST',   'api/cart/items'],
            'cart.items.update'                 => ['PUT',    'api/cart/items/{cartItemId}'],
            'cart.items.destroy'                => ['DELETE', 'api/cart/items/{cartItemId}'],
            // Orders
            'orders.store'                      => ['POST',   'api/orders'],
            'orders.index'                      => ['GET',    'api/orders'],
            'orders.show'                       => ['GET',    'api/orders/{order}'],
            // Review create (protected)
            'reviews.store'                     => ['POST',   'api/products/{productId}/reviews'],
        ];
    }

    // -----------------------------------------------------------------------
    // Middleware assertions (structural)
    // -----------------------------------------------------------------------

    /**
     * @dataProvider publicRouteProvider
     */
    public function test_public_routes_do_not_have_sanctum_middleware(string $method, string $uri): void
    {
        $route = $this->findRoute($method, $uri);

        $this->assertNotNull($route, "Route {$method} {$uri} not found.");

        $middleware = $route->gatherMiddleware();

        $this->assertNotContains(
            'auth:sanctum',
            $middleware,
            "Public route {$method} /{$uri} should NOT have auth:sanctum middleware.",
        );
    }

    /**
     * @dataProvider protectedRouteProvider
     */
    public function test_protected_routes_have_sanctum_middleware(string $method, string $uri): void
    {
        $route = $this->findRoute($method, $uri);

        $this->assertNotNull($route, "Route {$method} {$uri} not found.");

        $middleware = $route->gatherMiddleware();

        $sanctumMiddlewarePresent = in_array('auth:sanctum', $middleware, true)
            || in_array(\Illuminate\Auth\Middleware\Authenticate::class . ':sanctum', $middleware, true);

        $this->assertTrue(
            $sanctumMiddlewarePresent,
            "Protected route {$method} /{$uri} should have auth:sanctum middleware.",
        );
    }

    public function test_api_middleware_group_includes_resolve_language_and_currency(): void
    {
        /** @var Router $router */
        $router = app(Router::class);

        $apiGroup = $router->getMiddlewareGroups()['api'] ?? [];

        $this->assertContains(
            \App\Http\Middleware\ResolveLanguageAndCurrency::class,
            $apiGroup,
            'ResolveLanguageAndCurrency must be part of the api middleware group.',
        );
    }

    // -----------------------------------------------------------------------
    // HTTP behaviour assertions (integration)
    // -----------------------------------------------------------------------

    public function test_public_endpoint_is_accessible_without_authentication(): void
    {
        // GET /api/products — returns empty list when no products in DB
        $response = $this->getJson('/api/products');

        // 200 (or any 2xx) — the endpoint is open
        $response->assertSuccessful();
    }

    public function test_protected_endpoint_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }

    public function test_protected_endpoint_is_accessible_with_valid_sanctum_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        // 200 — authenticated request succeeds
        $response->assertStatus(200);
    }

    public function test_protected_endpoint_returns_401_when_token_is_missing_for_orders(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    public function test_protected_endpoint_returns_401_when_token_is_missing_for_customer_profile(): void
    {
        $response = $this->getJson('/api/customers/me');

        $response->assertStatus(401);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function assertRouteRegistered(string $method, string $uri): void
    {
        $route = $this->findRoute($method, $uri);

        $this->assertNotNull(
            $route,
            "Expected route {$method} /{$uri} to be registered but it was not found.",
        );
    }

    private function findRoute(string $method, string $uri): ?\Illuminate\Routing\Route
    {
        /** @var Router $router */
        $router = app(Router::class);

        foreach ($router->getRoutes() as $route) {
            if (
                in_array(strtoupper($method), $route->methods(), true)
                && $route->uri() === $uri
            ) {
                return $route;
            }
        }

        return null;
    }
}
