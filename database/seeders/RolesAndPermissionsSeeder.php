<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Standardized Permissions matching sidebar and controllers
        $permissions = [
            // Dashboard
            'dashboard-view',

            // Masters
            'uom-list', 'uom-create', 'uom-edit', 'uom-delete',
            'packing-type-list', 'packing-type-create', 'packing-type-edit', 'packing-type-delete',
            'product-category-list', 'product-category-create', 'product-category-edit', 'product-category-delete',
            'product-group-list', 'product-group-create', 'product-group-edit', 'product-group-delete',
            'product-list', 'product-create', 'product-edit', 'product-delete',

            // Inventory
            'warehouse-list', 'warehouse-create', 'warehouse-edit', 'warehouse-delete',
            'opening-stock-list', 'opening-stock-create', 'opening-stock-edit', 'opening-stock-delete',
            'inbound-list', 'inbound-create', 'inbound-edit', 'inbound-delete',
            'outbound-list', 'outbound-create', 'outbound-edit', 'outbound-delete',
            'stock-transfer-list', 'stock-transfer-create',

            // Expiry & QC
            'expiry-list', 'expiry-toggle',
            'qc-list', 'qc-manage',

            // Parties / Logistics
            'vendor-list', 'vendor-create', 'vendor-edit', 'vendor-delete',
            'customer-list', 'customer-create', 'customer-edit', 'customer-delete',
            'transporter-list', 'transporter-create', 'transporter-edit', 'transporter-delete',
            'arrived-from-list', 'arrived-from-create', 'arrived-from-edit', 'arrived-from-delete',

            // User & Security Management
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'shift-list', 'shift-create', 'shift-edit', 'shift-delete',
            'login-log-list',

            // Reports
            'report-inbound',
            'report-outbound',
            'report-warehouse-stock',
            'report-warehouse-capacity',
            'report-all-stocks',
            'report-stock-ledger',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 1. Super Admin Role
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Manager Role
        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $manager->syncPermissions([
            'dashboard-view',
            'inbound-list', 'inbound-create', 'inbound-edit',
            'outbound-list', 'outbound-create', 'outbound-edit',
            'stock-transfer-list', 'stock-transfer-create',
            'qc-list', 'qc-manage',
            'expiry-list', 'expiry-toggle',
            'report-inbound', 'report-outbound', 'report-warehouse-stock', 'report-warehouse-capacity', 'report-all-stocks', 'report-stock-ledger',
            'product-list', 'product-create', 'product-edit',
            'uom-list', 'packing-type-list', 'product-category-list', 'product-group-list',
            'warehouse-list', 'vendor-list', 'customer-list', 'transporter-list', 'arrived-from-list'
        ]);

        // 3. Warehouse Staff Role
        $warehouseStaff = Role::firstOrCreate(['name' => 'Warehouse Staff']);
        $warehouseStaff->syncPermissions([
            'dashboard-view',
            'inbound-list', 'inbound-create',
            'stock-transfer-list', 'stock-transfer-create',
            'product-list', 'warehouse-list'
        ]);

        // 4. Dispatcher Role
        $dispatcher = Role::firstOrCreate(['name' => 'Dispatcher']);
        $dispatcher->syncPermissions([
            'dashboard-view',
            'outbound-list', 'outbound-create',
            'product-list'
        ]);

        // 5. Viewer / Auditor Role
        $viewer = Role::firstOrCreate(['name' => 'Viewer / Auditor']);
        $viewer->syncPermissions([
            'dashboard-view',
            'report-inbound', 'report-outbound', 'report-warehouse-stock', 'report-warehouse-capacity', 'report-all-stocks', 'report-stock-ledger',
            'product-list', 'warehouse-list', 'expiry-list'
        ]);

        // Default Shifts
        Shift::firstOrCreate(['name' => 'Morning Shift'], ['start_time' => '08:00:00', 'end_time' => '16:00:00']);
        Shift::firstOrCreate(['name' => 'Evening Shift'], ['start_time' => '16:00:00', 'end_time' => '00:00:00']);
        Shift::firstOrCreate(['name' => 'Night Shift'], ['start_time' => '00:00:00', 'end_time' => '08:00:00']);
        Shift::firstOrCreate(['name' => 'Full Day (24/7)'], ['start_time' => '00:00:00', 'end_time' => '23:59:59']);

        // Create or Update Super Admin Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin'),
                'is_active' => true,
                'shift_id' => null,
            ]
        );

        $admin->update([
            'name' => 'Super Admin',
            'password' => Hash::make('admin'),
            'is_active' => true,
            'shift_id' => null,
        ]);

        $admin->syncRoles(['Super Admin']);

        // Ensure all other existing users have a role assigned
        $users = User::all();
        foreach ($users as $user) {
            if ($user->roles->isEmpty()) {
                $user->assignRole('Super Admin');
            }
        }
    }
}
