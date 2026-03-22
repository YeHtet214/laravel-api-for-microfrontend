<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->words(2, true),
            'sku' => strtoupper($this->faker->unique()->bothify('VAR-####-????')),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'stock_quantity' => $this->faker->numberBetween(0, 50),
            'status' => 'active',
        ];
    }
}
