<?php

namespace Tests\Unit\Resources;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Domains\Currency\Models\Currency;
use App\Http\Resources\Api\Cart\CartResource;
use App\Http\Resources\Api\Cart\CartItemResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Tests\TestCase;

class CartResourceTest extends TestCase
{
    public function test_cart_resource_has_expected_keys(): void
    {
        $cart = new Cart();
        $cart->setRawAttributes(['id' => 1]);
        $cart->setRelation('items', new Collection([]));

        $resource = new CartResource($cart);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function test_cart_resource_total_sums_item_line_totals(): void
    {
        $cart = new Cart();
        $cart->setRawAttributes(['id' => 1]);

        $variant = new ProductVariant();
        $variant->setRawAttributes(['id' => 1, 'regular_price' => '15.00', 'is_active' => true]);

        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'p',
            'name'        => json_encode(['en' => 'Prod']),
            'description' => json_encode(['en' => 'Desc']),
            'is_active'   => true,
        ]);

        $item1 = new CartItem();
        $item1->setRawAttributes(['id' => 1, 'quantity' => 2]);
        $item1->setRelation('product', $product);
        $item1->setRelation('productVariant', $variant);

        $item2 = new CartItem();
        $item2->setRawAttributes(['id' => 2, 'quantity' => 3]);
        $item2->setRelation('product', $product);
        $item2->setRelation('productVariant', $variant);

        $cart->setRelation('items', new Collection([$item1, $item2]));

        $resource = new CartResource($cart);
        $data = $resource->toArray(Request::create('/'));

        // (2 * 15.00) + (3 * 15.00) = 30.00 + 45.00 = 75.00
        $this->assertEqualsWithDelta(75.0, $data['total'], 0.001);
    }

    public function test_cart_item_resource_has_expected_keys(): void
    {
        $variant = new ProductVariant();
        $variant->setRawAttributes(['id' => 1, 'regular_price' => '10.00', 'is_active' => true]);

        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'test',
            'name'        => json_encode(['en' => 'Test']),
            'description' => json_encode(['en' => 'Desc']),
            'is_active'   => true,
        ]);

        $item = new CartItem();
        $item->setRawAttributes(['id' => 5, 'quantity' => 2]);
        $item->setRelation('product', $product);
        $item->setRelation('productVariant', $variant);

        $resource = new CartItemResource($item);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('product', $data);
        $this->assertArrayHasKey('variant', $data);
        $this->assertArrayHasKey('quantity', $data);
        $this->assertArrayHasKey('line_total', $data);
    }

    public function test_cart_item_resource_computes_line_total(): void
    {
        $variant = new ProductVariant();
        $variant->setRawAttributes(['id' => 1, 'regular_price' => '12.50', 'is_active' => true]);

        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'test',
            'name'        => json_encode(['en' => 'Test']),
            'description' => json_encode(['en' => 'Desc']),
            'is_active'   => true,
        ]);

        $item = new CartItem();
        $item->setRawAttributes(['id' => 1, 'quantity' => 4]);
        $item->setRelation('product', $product);
        $item->setRelation('productVariant', $variant);

        $resource = new CartItemResource($item);
        $data = $resource->toArray(Request::create('/'));

        // 4 * 12.50 = 50.00
        $this->assertEqualsWithDelta(50.0, $data['line_total'], 0.001);
    }

    public function test_cart_item_resource_applies_currency_conversion(): void
    {
        $variant = new ProductVariant();
        $variant->setRawAttributes(['id' => 1, 'regular_price' => '10.00', 'is_active' => true]);

        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'test',
            'name'        => json_encode(['en' => 'Test']),
            'description' => json_encode(['en' => 'Desc']),
            'is_active'   => true,
        ]);

        $item = new CartItem();
        $item->setRawAttributes(['id' => 1, 'quantity' => 2]);
        $item->setRelation('product', $product);
        $item->setRelation('productVariant', $variant);

        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '2.00']);

        $request = Request::create('/');
        $request->attributes->set('currency', $currency);

        $resource = new CartItemResource($item);
        $data = $resource->toArray($request);

        // 2 * 10.00 * 2.00 = 40.00
        $this->assertEqualsWithDelta(40.0, $data['line_total'], 0.001);
    }

    public function test_cart_item_resource_reads_currency_from_request_attributes(): void
    {
        $variant = new ProductVariant();
        $variant->setRawAttributes(['id' => 1, 'regular_price' => '20.00', 'is_active' => true]);

        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'test',
            'name'        => json_encode(['en' => 'Test']),
            'description' => json_encode(['en' => 'Desc']),
            'is_active'   => true,
        ]);

        $item = new CartItem();
        $item->setRawAttributes(['id' => 1, 'quantity' => 3]);
        $item->setRelation('product', $product);
        $item->setRelation('productVariant', $variant);

        // Currency stored via request->attributes (T3.1 middleware pattern)
        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '1.50']);

        $request = Request::create('/');
        $request->attributes->set('currency', $currency);

        $resource = new CartItemResource($item);
        $data = $resource->toArray($request);

        // 3 * 20.00 * 1.50 = 90.00
        $this->assertEqualsWithDelta(90.0, $data['line_total'], 0.001);
    }
}
