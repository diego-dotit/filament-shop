<?php

namespace Tests\Unit\Domains\Exceptions;

use App\Domains\CartProduct\Exceptions\InactiveVariantException;
use App\Domains\CartProduct\Exceptions\InsufficientStockException;
use App\Domains\CustomerOrder\Exceptions\UnauthorizedAddressException;
use App\Domains\OrderPlacement\Exceptions\EmptyCartException;
use App\Domains\Shared\Exceptions\OrderPlacementException;
use LogicException;
use PHPUnit\Framework\TestCase;

class CustomExceptionsTest extends TestCase
{
    // InsufficientStockException

    public function test_insufficient_stock_exception_extends_logic_exception(): void
    {
        $exception = new InsufficientStockException('test', 5, 2);

        $this->assertInstanceOf(LogicException::class, $exception);
    }

    public function test_insufficient_stock_exception_message_contains_variant_and_quantities(): void
    {
        $exception = new InsufficientStockException('Blue T-Shirt', 10, 3);

        $message = $exception->getMessage();

        $this->assertStringContainsString('Blue T-Shirt', $message);
        $this->assertStringContainsString('10', $message);
        $this->assertStringContainsString('3', $message);
    }

    public function test_insufficient_stock_exception_message_is_user_friendly(): void
    {
        $exception = new InsufficientStockException('Red Sneakers SKU-99', 5, 1);

        $message = $exception->getMessage();

        $this->assertStringNotContainsString('SQL', $message);
        $this->assertStringNotContainsString('Exception', $message);
        $this->assertNotEmpty($message);
    }

    // InactiveVariantException

    public function test_inactive_variant_exception_extends_logic_exception(): void
    {
        $exception = new InactiveVariantException('SKU-001');

        $this->assertInstanceOf(LogicException::class, $exception);
    }

    public function test_inactive_variant_exception_message_contains_variant_name(): void
    {
        $exception = new InactiveVariantException('SKU-001');

        $message = $exception->getMessage();

        $this->assertStringContainsString('SKU-001', $message);
        $this->assertStringContainsString('no longer available', $message);
    }

    // EmptyCartException

    public function test_empty_cart_exception_extends_logic_exception(): void
    {
        $exception = new EmptyCartException();

        $this->assertInstanceOf(LogicException::class, $exception);
    }

    public function test_empty_cart_exception_has_descriptive_message(): void
    {
        $exception = new EmptyCartException();

        $message = $exception->getMessage();

        $this->assertStringContainsString('empty', $message);
        $this->assertNotEmpty($message);
    }

    // UnauthorizedAddressException

    public function test_unauthorized_address_exception_extends_exception(): void
    {
        $exception = new UnauthorizedAddressException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertNotInstanceOf(LogicException::class, $exception);
    }

    public function test_unauthorized_address_exception_message_indicates_address_ownership(): void
    {
        $exception = new UnauthorizedAddressException();

        $message = $exception->getMessage();

        $this->assertStringContainsString('address', $message);
        $this->assertStringContainsString('customer', $message);
    }

    // OrderPlacementException

    public function test_order_placement_exception_extends_exception(): void
    {
        $exception = new OrderPlacementException('Something went wrong');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_order_placement_exception_accepts_custom_message(): void
    {
        $exception = new OrderPlacementException('Order could not be placed at this time');

        $this->assertSame('Order could not be placed at this time', $exception->getMessage());
    }
}
