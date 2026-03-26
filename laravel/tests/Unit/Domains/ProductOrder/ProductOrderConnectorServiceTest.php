<?php

namespace Tests\Unit\Domains\ProductOrder;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Domains\ProductOrder\Exceptions\InactiveVariantException;
use App\Domains\ProductOrder\ProductOrderConnectorService;
use App\Services\CurrencyService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ProductOrderConnectorServiceTest extends TestCase
{
    private CurrencyService $currencyService;
    private ProductOrderConnectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = new CurrencyService();
        $this->service         = new ProductOrderConnectorService($this->currencyService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeProduct(string $name, string $langCode = 'en'): Product
    {
        $product = new Product();
        $product->setRawAttributes([
            'id'        => 1,
            'name'      => json_encode([$langCode => $name]),
            'is_active' => true,
        ]);

        return $product;
    }

    private function makeVariant(Product $product, array $attrs = []): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->setRawAttributes(array_merge([
            'id'            => 10,
            'product_id'    => $product->id,
            'sku'           => 'SKU-001',
            'regular_price' => '50.00',
            'special_price' => null,
            'is_active'     => true,
        ], $attrs));

        // Attach the product relation
        $variant->setRelation('product', $product);

        return $variant;
    }

    private function makeCartItem(ProductVariant $variant, int $quantity = 2): CartItem
    {
        $item = new CartItem();
        $item->setRawAttributes([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => $quantity,
        ]);

        $item->setRelation('productVariant', $variant);

        return $item;
    }

    private function makeCart(array $cartItems): Cart
    {
        $cart = Mockery::mock(Cart::class)->makePartial();
        $cart->shouldReceive('getAttribute')
             ->with('items')
             ->andReturn(collect($cartItems));

        return $cart;
    }

    private function makeBaseCurrency(): Currency
    {
        $currency = new Currency();
        $currency->setRawAttributes(['code' => 'USD', 'is_base' => true, 'exchange_rate' => '1.000000']);

        return $currency;
    }

    private function makeLang(string $code): Language
    {
        $lang = new Language();
        $lang->setRawAttributes(['code' => $code]);

        return $lang;
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_builds_order_items_with_correct_snapshot_fields(): void
    {
        $product  = $this->makeProduct('Test Product');
        $variant  = $this->makeVariant($product, ['sku' => 'SKU-ABC', 'regular_price' => '25.00']);
        $cartItem = $this->makeCartItem($variant, 3);
        $cart     = $this->makeCart([$cartItem]);
        $currency = $this->makeBaseCurrency();

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);

        /** @var OrderItem $item */
        $item = $result->first();
        $this->assertInstanceOf(OrderItem::class, $item);
        $this->assertSame(1, $item->product_id);
        $this->assertSame(10, $item->product_variant_id);
        $this->assertSame(3, $item->quantity);
        $this->assertSame('Test Product', $item->product_name_snapshot);
        $this->assertSame('SKU-ABC', $item->variant_sku_snapshot);
    }

    public function test_uses_regular_price_when_special_price_is_null(): void
    {
        $product  = $this->makeProduct('Product A');
        $variant  = $this->makeVariant($product, ['regular_price' => '100.00', 'special_price' => null]);
        $cartItem = $this->makeCartItem($variant, 1);
        $cart     = $this->makeCart([$cartItem]);
        $currency = $this->makeBaseCurrency();

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency);

        $item = $result->first();
        $this->assertEqualsWithDelta(100.00, (float) $item->unit_price_snapshot, 0.001);
    }

    public function test_uses_special_price_when_not_null(): void
    {
        $product  = $this->makeProduct('Product B');
        $variant  = $this->makeVariant($product, ['regular_price' => '100.00', 'special_price' => '79.99']);
        $cartItem = $this->makeCartItem($variant, 1);
        $cart     = $this->makeCart([$cartItem]);
        $currency = $this->makeBaseCurrency();

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency);

        $item = $result->first();
        $this->assertEqualsWithDelta(79.99, (float) $item->unit_price_snapshot, 0.001);
    }

    public function test_applies_currency_conversion(): void
    {
        $product  = $this->makeProduct('Product C');
        $variant  = $this->makeVariant($product, ['regular_price' => '10.00']);
        $cartItem = $this->makeCartItem($variant, 1);
        $cart     = $this->makeCart([$cartItem]);

        $currency = new Currency();
        $currency->setRawAttributes(['code' => 'EUR', 'is_base' => false, 'exchange_rate' => '2.00']);

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency);

        $item = $result->first();
        // 10.00 * 2.00 = 20.00
        $this->assertEqualsWithDelta(20.00, (float) $item->unit_price_snapshot, 0.001);
    }

    public function test_calculates_line_total_correctly(): void
    {
        $product  = $this->makeProduct('Product D');
        $variant  = $this->makeVariant($product, ['regular_price' => '15.00']);
        $cartItem = $this->makeCartItem($variant, 4);
        $cart     = $this->makeCart([$cartItem]);
        $currency = $this->makeBaseCurrency();

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency);

        $item = $result->first();
        // 15.00 * 4 = 60.00
        $this->assertEqualsWithDelta(60.00, (float) $item->line_total_snapshot, 0.001);
    }

    public function test_applies_language_translation(): void
    {
        $product = new Product();
        $product->setRawAttributes([
            'id'        => 2,
            'name'      => json_encode(['en' => 'English Name', 'fr' => 'Nom Français']),
            'is_active' => true,
        ]);
        // makeVariant already sets product_id from $product->id; no need to override
        $variant  = $this->makeVariant($product);
        $cartItem = $this->makeCartItem($variant, 1);
        $cart     = $this->makeCart([$cartItem]);
        $currency = $this->makeBaseCurrency();

        $lang = $this->makeLang('fr');

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency, $lang);

        $item = $result->first();
        $this->assertSame('Nom Français', $item->product_name_snapshot);
    }

    public function test_returns_collection_of_unsaved_order_items(): void
    {
        $product  = $this->makeProduct('Product E');
        $variant  = $this->makeVariant($product);
        $cartItem = $this->makeCartItem($variant, 1);
        $cart     = $this->makeCart([$cartItem]);
        $currency = $this->makeBaseCurrency();

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency);

        $this->assertInstanceOf(Collection::class, $result);
        $result->each(function ($item) {
            $this->assertInstanceOf(OrderItem::class, $item);
            // Not persisted: no primary key
            $this->assertNull($item->id);
            $this->assertFalse($item->exists);
        });
    }

    public function test_throws_inactive_variant_exception_for_deactivated_variant(): void
    {
        $this->expectException(InactiveVariantException::class);

        $product  = $this->makeProduct('Product F');
        $variant  = $this->makeVariant($product, ['is_active' => false]);
        $cartItem = $this->makeCartItem($variant, 1);
        $cart     = $this->makeCart([$cartItem]);
        $currency = $this->makeBaseCurrency();

        $this->service->buildOrderItemsFromCartItems($cart, $currency);
    }

    public function test_returns_multiple_order_items_for_multiple_cart_items(): void
    {
        $product1  = $this->makeProduct('Product 1');
        $product1->setRawAttributes(array_merge($product1->getRawOriginal(), ['id' => 1]));
        $variant1  = $this->makeVariant($product1, ['id' => 10, 'sku' => 'SKU-1', 'regular_price' => '10.00']);
        $cartItem1 = $this->makeCartItem($variant1, 2);

        $product2 = new Product();
        $product2->setRawAttributes([
            'id'        => 2,
            'name'      => json_encode(['en' => 'Product 2']),
            'is_active' => true,
        ]);
        $variant2  = $this->makeVariant($product2, ['id' => 20, 'sku' => 'SKU-2', 'regular_price' => '30.00']);
        $cartItem2 = $this->makeCartItem($variant2, 1);

        $cart     = $this->makeCart([$cartItem1, $cartItem2]);
        $currency = $this->makeBaseCurrency();

        $result = $this->service->buildOrderItemsFromCartItems($cart, $currency);

        $this->assertCount(2, $result);
    }
}
