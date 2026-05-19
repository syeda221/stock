<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // User & Roles
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',

            // Masters
            'uom-list', 'uom-create', 'uom-edit', 'uom-delete',
            'packing-type-list', 'packing-type-create', 'packing-type-edit', 'packing-type-delete',
            'product-category-list', 'product-category-create', 'product-category-edit', 'product-category-delete',
            'product-group-list', 'product-group-create', 'product-group-edit', 'product-group-delete',
            'product-list', 'product-create', 'product-edit', 'product-delete',
            'warehouse-list', 'warehouse-create', 'warehouse-edit', 'warehouse-delete',
            
            // Parties / Logistics
            'vendor-list', 'vendor-create', 'vendor-edit', 'vendor-delete',
            'customer-list', 'customer-create', 'customer-edit', 'customer-delete',
            'transporter-list', 'transporter-create', 'transporter-edit', 'transporter-delete',
            'arrived-from-list', 'arrived-from-create', 'arrived-from-edit', 'arrived-from-delete',
            
            // Inventory
            'opening-stock-list', 'opening-stock-create',
            'inbound-list', 'inbound-create', 'inbound-edit', 'inbound-delete', 'inbound-print', 'inbound-invoice',
            'outbound-list', 'outbound-create', 'outbound-edit', 'outbound-delete', 'outbound-print', 'outbound-invoice', 'outbound-dc',
            
            // Reports
            'report-inbound',
            'report-outbound',
            'report-warehouse-stock',
            'report-warehouse-capacity',
            'report-all-stocks',
            'report-stock-ledger'
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $staff = Role::firstOrCreate(['name' => 'Staff']);

        // Assign all permissions to Super Admin
        $superAdmin->syncPermissions(Permission::all());

        // Assign some permissions to Admin
        $adminPermissions = array_diff($permissions, [
            'role-list', 'role-create', 'role-edit', 'role-delete'
        ]);
        $admin->syncPermissions($adminPermissions);

        // Assign Manager Permissions
        $managerPermissions = [
            'product-list', 'warehouse-list', 'vendor-list', 'customer-list',
            'inbound-list', 'inbound-create', 'inbound-edit', 'inbound-print', 'inbound-invoice',
            'outbound-list', 'outbound-create', 'outbound-edit', 'outbound-print', 'outbound-invoice', 'outbound-dc',
            'report-inbound', 'report-outbound', 'report-warehouse-stock', 'report-all-stocks'
        ];
        $manager->syncPermissions($managerPermissions);

        // Assign Staff Permissions
        $staffPermissions = [
            'product-list', 'inbound-list', 'outbound-list'
        ];
        $staff->syncPermissions($staffPermissions);

        // Ensure Admin user gets Super Admin role
        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            $user->assignRole('Super Admin');
        }
    }
}
