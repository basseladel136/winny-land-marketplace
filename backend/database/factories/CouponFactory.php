<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'             => strtoupper($this->faker->unique()->bothify('??###')),
            'type'             => $this->faker->randomElement(['percent', 'fixed']),
            'value'            => $this->faker->randomFloat(2, 5, 50),
            'min_order_amount' => 0,
            'max_uses'         => null,
            'uses_count'       => 0,
            'is_active'        => true,
            'expires_at'       => null,
        ];
    }
}
