<?php

namespace Tests\Unit\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

class OrderAddressTest extends TestCase
{
    public function test_order_address_class_exists(): void
    {
        $this->assertTrue(class_exists(OrderAddress::class));
    }

    public function test_order_address_can_be_instantiated(): void
    {
        $address = new OrderAddress();

        $this->assertInstanceOf(OrderAddress::class, $address);
    }

    public function test_order_address_fillable_contains_address_fields(): void
    {
        $address = new OrderAddress();

        $fillable = $address->getFillable();

        $this->assertContains('country', $fillable);
        $this->assertContains('city', $fillable);
        $this->assertContains('address_line_1', $fillable);
        $this->assertContains('postcode', $fillable);
    }

    public function test_order_address_fillable_contains_type_field(): void
    {
        $address = new OrderAddress();

        $this->assertContains('type', $address->getFillable());
    }

    public function test_order_address_order_relationship_is_belongs_to(): void
    {
        $address = new OrderAddress();

        $this->assertInstanceOf(BelongsTo::class, $address->order());
    }

    public function test_order_address_order_relationship_points_to_order_model(): void
    {
        $address = new OrderAddress();

        $this->assertInstanceOf(Order::class, $address->order()->getRelated());
    }

    public function test_order_address_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass(OrderAddress::class);

        $this->assertSame('App\Domains\Order\Models', $reflection->getNamespaceName());
    }
}
