<?php

namespace Database\Factories;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 5, 500);
        $quantity  = fake()->numberBetween(1, 10);

        return [
            'order_id'              => fn () => Order::factory(),
            'product_id'            => fn () => Product::factory(),
            'product_variant_id'    => fn () => ProductVariant::factory(),
            'product_name_snapshot' => fake()->words(3, true),
            'variant_sku_snapshot'  => strtoupper(fake()->bothify('SKU-####-???')),
            'unit_price_snapshot'   => $unitPrice,
            'quantity'              => $quantity,
            'line_total_snapshot'   => round($unitPrice * $quantity, 2),
        ];
    }
}
