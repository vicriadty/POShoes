<?php

namespace Database\Seeders;

use App\Models\ServiceCatalog;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Seed default service catalog (docs/design/service-catalog-payments.md).
 * Harga dasar integer rupiah, snapshot saat penerimaan (ADR D2).
 */
class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Cleaning' => 'CLN',
            'Repaint' => 'PNT',
            'Repair' => 'RPR',
            'Add-on' => 'ADD',
        ];

        $categoryIds = [];
        foreach ($categories as $name => $code) {
            $categoryIds[$code] = ServiceCategory::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'active' => true],
            )->id;
        }

        $services = [
            ['code' => 'BC', 'name' => 'Basic Cleaning', 'category' => 'CLN', 'price' => 40000, 'minutes' => 30, 'photo' => true],
            ['code' => 'DC', 'name' => 'Deep Cleaning', 'category' => 'CLN', 'price' => 75000, 'minutes' => 60, 'photo' => true],
            ['code' => 'FC', 'name' => 'Fast Cleaning', 'category' => 'CLN', 'price' => 25000, 'minutes' => 20, 'photo' => true],
            ['code' => 'RP', 'name' => 'Repaint', 'category' => 'PNT', 'price' => 120000, 'minutes' => 120, 'photo' => true],
            ['code' => 'RSL', 'name' => 'Repair Sole', 'category' => 'RPR', 'price' => 90000, 'minutes' => 90, 'photo' => true],
            ['code' => 'RST', 'name' => 'Repair Stitching', 'category' => 'RPR', 'price' => 50000, 'minutes' => 60, 'photo' => true],
            ['code' => 'GT', 'name' => 'Ganti Tali', 'category' => 'ADD', 'price' => 15000, 'minutes' => 10, 'photo' => false],
            ['code' => 'AD', 'name' => 'Add-on Deodorizing', 'category' => 'ADD', 'price' => 20000, 'minutes' => 15, 'photo' => false],
        ];

        foreach ($services as $svc) {
            ServiceCatalog::firstOrCreate(
                ['code' => $svc['code']],
                [
                    'name' => $svc['name'],
                    'category_id' => $categoryIds[$svc['category']],
                    'base_price' => $svc['price'],
                    'estimated_duration_minutes' => $svc['minutes'],
                    'requires_before_after_photo' => $svc['photo'],
                    'active' => true,
                ],
            );
        }
    }
}
