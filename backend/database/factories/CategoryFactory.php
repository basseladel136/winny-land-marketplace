<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en'    => $this->faker->unique()->word() . ' Category',
            'name_ar'    => 'فئة ' . $this->faker->unique()->word(),
            'is_active'  => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
