<?php

namespace Database\Factories;

use App\Domain\ServiceOrders\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrder>
 */
class ServiceOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'SO-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'branch_id' => Branch::factory(),
            'received_by' => User::factory(),
            'status' => OrderStatus::Draft,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 0,
            'customer_notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
        ];
    }
}
