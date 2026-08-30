<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menyediakan role dan permission dasar (lihat docs/design/roles-permissions.md).
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Auth & akun
            'auth.login', 'auth.logout', 'users.view_own',
            // User & role
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.assign',
            // Customer
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            // Service catalog
            'services.view', 'services.create', 'services.update', 'services.delete',
            // Service order
            'service_orders.view', 'service_orders.create', 'service_orders.update',
            'service_orders.approve', 'service_orders.change_status',
            'service_orders.assign', 'service_orders.pickup', 'service_orders.cancel',
            // Payment & invoice
            'payments.create', 'payments.view', 'payments.void', 'payments.refund',
            'invoices.view', 'invoices.send',
            // Teknisi
            'work.view', 'work.item_status', 'work.notes', 'work.photos', 'work.material_usage',
            // Inventory
            'inventory.items.view', 'inventory.adjust', 'inventory.usage', 'inventory.stocktake',
            // Reporting
            'reports.view', 'reports.export',
            // Messaging
            'whatsapp.view', 'whatsapp.resend',
            // Audit
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'owner' => $permissions,
            'admin' => [
                'auth.login', 'auth.logout', 'users.view_own',
                'users.view', 'users.create', 'users.update',
                'roles.view', 'roles.assign',
                'customers.view', 'customers.create', 'customers.update', 'customers.delete',
                'services.view', 'services.create', 'services.update', 'services.delete',
                'service_orders.view', 'service_orders.create', 'service_orders.update',
                'service_orders.approve', 'service_orders.change_status',
                'service_orders.assign', 'service_orders.pickup', 'service_orders.cancel',
                'payments.create', 'payments.view', 'payments.void', 'payments.refund',
                'invoices.view', 'invoices.send',
                'work.view', 'work.item_status', 'work.notes', 'work.photos', 'work.material_usage',
                'inventory.items.view', 'inventory.adjust', 'inventory.usage', 'inventory.stocktake',
                'reports.view', 'reports.export',
                'whatsapp.view', 'whatsapp.resend',
                'audit.view',
            ],
            'kasir' => [
                'auth.login', 'auth.logout', 'users.view_own',
                'customers.view', 'customers.create', 'customers.update',
                'services.view',
                'service_orders.view', 'service_orders.create', 'service_orders.update',
                'service_orders.change_status', 'service_orders.pickup',
                'payments.create', 'payments.view',
                'invoices.view', 'invoices.send',
            ],
            'teknisi' => [
                'auth.login', 'auth.logout', 'users.view_own',
                'service_orders.view',
                'work.view', 'work.item_status', 'work.notes', 'work.photos', 'work.material_usage',
                'inventory.items.view', 'inventory.usage',
            ],
        ];

        foreach ($roles as $role => $perms) {
            $roleModel = Role::firstOrCreate(['name' => $role]);
            $roleModel->syncPermissions($perms);
        }
    }
}
