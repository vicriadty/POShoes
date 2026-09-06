<?php

namespace Database\Factories;

use App\Models\StockItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockItem>
 */
class StockItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->lexify('STK-????')),
            'name' => fake()->unique()->words(2, true),
            'unit' => fake()->randomElement(['ml', 'gr', 'pcs', 'pasang']),
            'min_stock' => fake()->numberBetween(0, 10),
            'active' => true,
            'notes' => null,
        ];
    }
}
