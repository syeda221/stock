<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        $groupedPermissions = $this->groupPermissions($permissions);
        return view('roles.create', compact('permissions', 'groupedPermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => $request->name]);
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $groupedPermissions = $this->groupPermissions($permissions);
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'groupedPermissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return redirect()->route('roles.index')->with('error', 'Cannot delete Super Admin role.');
        }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    private function groupPermissions($permissions)
    {
        $grouped = [];
        foreach ($permissions as $p) {
            $parts = explode('-', $p->name);
            $module = count($parts) > 1 ? ucfirst(end($parts)) : 'General';
            // Custom module mapping
            if (str_contains($p->name, 'inbound')) $module = 'Inbound Management';
            elseif (str_contains($p->name, 'outbound')) $module = 'Outbound Management';
            elseif (str_contains($p->name, 'stock-transfer')) $module = 'Stock Transfers';
            elseif (str_contains($p->name, 'qc')) $module = 'Quality Control (QC)';
            elseif (str_contains($p->name, 'expiry')) $module = 'Expiry & Sale Controls';
            elseif (str_contains($p->name, 'report')) $module = 'Reports & Analytics';
            elseif (str_contains($p->name, 'product')) $module = 'Products Catalog';
            elseif (str_contains($p->name, 'master')) $module = 'Master Data';
            elseif (str_contains($p->name, 'warehouse')) $module = 'Warehouses';
            elseif (str_contains($p->name, 'user') || str_contains($p->name, 'role') || str_contains($p->name, 'shift') || str_contains($p->name, 'log')) $module = 'Security & Administration';
            elseif (str_contains($p->name, 'dashboard')) $module = 'Dashboard';

            $grouped[$module][] = $p;
        }
        return $grouped;
    }
}
