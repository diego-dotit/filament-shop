<?php

namespace Database\Factories;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id'            => fn () => Cart::factory(),
            'product_id'         => fn () => Product::factory(),
            'product_variant_id' => fn () => ProductVariant::factory(),
            'quantity'           => fake()->numberBetween(1, 10),
        ];
    }
}
