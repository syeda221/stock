<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Super Admin Role exists with ALL permissions
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $permissions = Permission::all();
        $role->syncPermissions($permissions);

        // 2. Create/Update Super Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Super Admin']
        );

        $admin->update([
            'name' => 'Super Admin',
            'password' => Hash::make('admin'),
            'is_active' => true,
            'shift_id' => null, // 24/7 access
        ]);

        $admin->syncRoles(['Super Admin']);
    }
}
