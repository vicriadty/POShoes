<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        $phone = '08'.fake()->numerify('##########');

        return [
            'name' => fake()->name(),
            'phone_wa' => $phone,
            'phone_wa_normalized' => PhoneNormalizer::normalize($phone),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
            'communication_consent_at' => fake()->optional()->dateTime(),
            'created_by' => User::factory(),
        ];
    }
}
