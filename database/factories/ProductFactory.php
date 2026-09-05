<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => \App\Models\Tenant::factory(),
            'category_id' => \App\Models\Category::factory(),
            'name' => fake()->productName(),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(1000, 100000),
            'cost_price' => fake()->numberBetween(500, 50000),
            'stock' => fake()->numberBetween(0, 1000),
            'min_stock' => fake()->numberBetween(1, 10),
            'barcode' => fake()->ean13(),
        ];
    }
}
