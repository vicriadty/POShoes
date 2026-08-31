<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashierShift>
 */
class CashierShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'opening_balance' => fake()->numberBetween(0, 500000),
            'closed_balance' => null,
            'expected_amount' => null,
            'discrepancy' => null,
            'opened_at' => now(),
            'closed_at' => null,
            'notes' => null,
        ];
    }
}
