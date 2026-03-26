<?php

namespace Database\Factories;

use App\Domains\Cart\Models\Cart;
use App\Domains\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'customer_id' => fn () => Customer::factory(),
        ];
    }
}
