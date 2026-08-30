<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.now()->format('Ym').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'service_order_id' => ServiceOrder::factory(),
            'status' => 'issued',
            'issued_at' => now(),
            'due_at' => null,
            'sent_at' => null,
        ];
    }
}
