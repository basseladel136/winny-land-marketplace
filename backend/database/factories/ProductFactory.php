<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $nameEn = $this->faker->unique()->words(3, true);

        return [
            'category_id'    => Category::factory(),
            'name_en'        => ucwords($nameEn),
            'name_ar'        => 'منتج ' . $this->faker->unique()->word(),
            'description_en' => $this->faker->paragraph(),
            'description_ar' => 'وصف المنتج: ' . $this->faker->sentence(),
            'price'          => $this->faker->randomFloat(2, 10, 5000),
            'compare_price'  => null,
            'stock'          => $this->faker->numberBetween(0, 100),
            'sku'            => strtoupper($this->faker->unique()->bothify('WL-####-???')),
            'image'          => null,
            'images'         => [],
            'is_active'      => true,
            'is_featured'    => $this->faker->boolean(20),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
