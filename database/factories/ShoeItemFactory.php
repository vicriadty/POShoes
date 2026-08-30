<?php

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\ShoeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoeItem>
 */
class ShoeItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'brand' => fake()->randomElement(['Adidas', 'Nike', 'Converse', 'Vans', 'New Balance']),
            'model' => fake()->optional()->word(),
            'color' => fake()->optional()->safeColorName(),
            'size' => (string) fake()->numberBetween(36, 44),
            'material' => fake()->optional()->randomElement(['Kulit', 'Canvas', 'Suede', 'Mesh']),
            'condition_summary' => fake()->optional()->sentence(),
            'customer_description' => fake()->optional()->sentence(),
            'internal_description' => null,
        ];
    }
}
