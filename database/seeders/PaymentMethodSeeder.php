<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Seed metode pembayaran configurable (docs/design/service-catalog-payments.md).
 * Semua metode manual-confirm di MVP; gateway otomatis di iterasi lanjut.
 */
class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'cash', 'name' => 'Tunai', 'active' => true, 'sort' => 10],
            ['code' => 'transfer', 'name' => 'Transfer Bank', 'active' => true, 'sort' => 20],
            ['code' => 'qris', 'name' => 'QRIS', 'active' => true, 'sort' => 30],
            ['code' => 'card', 'name' => 'Kartu Debit/Kredit', 'active' => true, 'sort' => 40],
            ['code' => 'other', 'name' => 'Lainnya', 'active' => false, 'sort' => 90],
        ];

        foreach ($methods as $m) {
            PaymentMethod::firstOrCreate(
                ['code' => $m['code']],
                [
                    'name' => $m['name'],
                    'type' => 'manual',
                    'active' => $m['active'],
                    'sort_order' => $m['sort'],
                ],
            );
        }
    }
}
