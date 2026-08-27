<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Workstation;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'HQ'],
            ['name' => 'Cabang Utama', 'address' => 'Jl. Contoh No. 1', 'is_active' => true],
        );

        Workstation::firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Kasir 1'],
            ['code' => 'CASHIER-1', 'is_active' => true],
        );

        Workstation::firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Workbench 1'],
            ['code' => 'WORKBENCH-1', 'is_active' => true],
        );
    }
}
