<?php

namespace Database\Factories;

use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCatalog>
 */
class ServiceCatalogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('SVC-??')),
            'category_id' => ServiceCategory::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'base_price' => fake()->numberBetween(10000, 200000),
            'estimated_duration_minutes' => fake()->randomElement([20, 30, 60, 90, 120]),
            'requires_before_after_photo' => fake()->boolean(70),
            'active' => true,
        ];
    }
}
