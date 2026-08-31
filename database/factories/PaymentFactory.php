<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'payment_number' => 'PAY-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'amount' => fake()->numberBetween(10000, 200000),
            'received_at' => now(),
            'received_by' => User::factory(),
            'reference' => null,
            'voided_by' => null,
            'voided_at' => null,
            'void_reason' => null,
            'refunded_from' => null,
        ];
    }
}
