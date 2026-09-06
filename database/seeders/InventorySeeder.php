<?php

namespace Database\Seeders;

use App\Domain\Inventory\Actions\AdjustStockBalance;
use App\Models\Branch;
use App\Models\StockItem;
use Illuminate\Database\Seeder;

/**
 * Seed sample inventory bahan (Phase 6).
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'HQ'],
            ['name' => 'Cabang Utama', 'is_active' => true],
        );

        $items = [
            ['sku' => 'CLN-SOLVENT', 'name' => 'Solvent Pembersih', 'unit' => 'ml', 'min' => 500, 'qty' => 2000],
            ['sku' => 'POLISH-01', 'name' => 'Poles Cat', 'unit' => 'ml', 'min' => 200, 'qty' => 800],
            ['sku' => 'GLUE-01', 'name' => 'Lem Sol', 'unit' => 'gr', 'min' => 100, 'qty' => 300],
            ['sku' => 'THREAD-01', 'name' => 'Benang Jahit', 'unit' => 'pcs', 'min' => 20, 'qty' => 80],
            ['sku' => 'LACE-01', 'name' => 'Tali Sepatu', 'unit' => 'pasang', 'min' => 50, 'qty' => 120],
        ];

        foreach ($items as $data) {
            $item = StockItem::firstOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'unit' => $data['unit'],
                    'min_stock' => $data['min'],
                ],
            );

            AdjustStockBalance::apply($item, $branch->id, $data['qty'], 'stock_in');
        }
    }
}
