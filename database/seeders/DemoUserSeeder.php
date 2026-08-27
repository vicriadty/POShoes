<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'HQ'],
            ['name' => 'Cabang Utama', 'is_active' => true],
        );

        $users = [
            ['name' => 'Owner', 'email' => 'owner@poshoes.test', 'role' => 'owner'],
            ['name' => 'Admin', 'email' => 'admin@poshoes.test', 'role' => 'admin'],
            ['name' => 'Kasir', 'email' => 'kasir@poshoes.test', 'role' => 'kasir'],
            ['name' => 'Teknisi', 'email' => 'teknisi@poshoes.test', 'role' => 'teknisi'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'branch_id' => $branch->id,
                    'is_active' => true,
                ],
            );

            $user->assignRole($data['role']);
        }
    }
}
