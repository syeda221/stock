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

        // Permissions list by module
        $permissions = [
            // Dashboard
            'view-dashboard',
            
            // Inbound
            'view-inbound',
            'create-inbound',
            'edit-inbound',
            'delete-inbound',
            
            // Outbound
            'view-outbound',
            'create-outbound',
            'edit-outbound',
            'delete-outbound',
            
            // Stock Transfer
            'view-stock-transfers',
            'create-stock-transfers',
            
            // QC Management
            'view-qc',
            'manage-qc',

            // Expiry & Sales Toggle
            'view-expiry',
            'toggle-expiry-sale',
            
            // Reports
            'view-reports',
            'export-reports',
            
            // Products & Masters
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            'view-masters',
            'manage-masters',
            
            // Warehouses
            'view-warehouses',
            'manage-warehouses',
            
            // User & Security Management
            'view-users',
            'manage-users',
            'view-roles',
            'manage-roles',
            'view-shifts',
            'manage-shifts',
            'view-login-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles definition
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $manager->syncPermissions([
            'view-dashboard',
            'view-inbound', 'create-inbound', 'edit-inbound',
            'view-outbound', 'create-outbound', 'edit-outbound',
            'view-stock-transfers', 'create-stock-transfers',
            'view-qc', 'manage-qc',
            'view-expiry', 'toggle-expiry-sale',
            'view-reports', 'export-reports',
            'view-products', 'create-products', 'edit-products',
            'view-masters', 'view-warehouses'
        ]);

        $warehouseStaff = Role::firstOrCreate(['name' => 'Warehouse Staff']);
        $warehouseStaff->syncPermissions([
            'view-dashboard',
            'view-inbound', 'create-inbound',
            'view-stock-transfers', 'create-stock-transfers',
            'view-products', 'view-warehouses'
        ]);

        $dispatcher = Role::firstOrCreate(['name' => 'Dispatcher']);
        $dispatcher->syncPermissions([
            'view-dashboard',
            'view-outbound', 'create-outbound',
            'view-products'
        ]);

        $viewer = Role::firstOrCreate(['name' => 'Viewer / Auditor']);
        $viewer->syncPermissions([
            'view-dashboard',
            'view-reports',
            'view-products',
            'view-warehouses',
            'view-expiry'
        ]);

        // Default Shifts
        Shift::firstOrCreate(['name' => 'Morning Shift'], ['start_time' => '08:00:00', 'end_time' => '16:00:00']);
        Shift::firstOrCreate(['name' => 'Evening Shift'], ['start_time' => '16:00:00', 'end_time' => '00:00:00']);
        Shift::firstOrCreate(['name' => 'Night Shift'], ['start_time' => '00:00:00', 'end_time' => '08:00:00']);
        Shift::firstOrCreate(['name' => 'Full Day (24/7)'], ['start_time' => '00:00:00', 'end_time' => '23:59:59']);

        // Create or Update Super Admin Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Super Admin']
        );

        $admin->update([
            'name' => 'Super Admin',
            'password' => Hash::make('admin'),
            'is_active' => true,
            'shift_id' => null, // 24/7 Unlimited Access
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
