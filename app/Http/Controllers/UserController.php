<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'shift'])->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $shifts = Shift::all();
        return view('users.create', compact('roles', 'shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4',
            'shift_id' => 'nullable|exists:shifts,id',
            'roles' => 'array'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'shift_id' => $request->shift_id,
            'is_active' => true,
        ]);

        if ($request->has('roles')) {
            $user->assignRole($request->roles);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $shifts = Shift::all();
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('users.edit', compact('user', 'roles', 'shifts', 'userRoles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:4',
            'shift_id' => 'nullable|exists:shifts,id',
            'roles' => 'array'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'shift_id' => $request->shift_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles($request->roles ?? []);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->hasRole('Super Admin') && $user->is_active) {
            return redirect()->route('users.index')->with('error', 'Super Admin status cannot be suspended.');
        }

        $user->is_active = !$user->is_active;
        if (!$user->is_active) {
            $user->session_id = null; // Force logout if suspended
        }
        $user->save();

        $status = $user->is_active ? 'activated' : 'suspended';
        return redirect()->route('users.index')->with('success', "User {$user->name} has been {$status}.");
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('Super Admin')) {
            return redirect()->route('users.index')->with('error', 'Cannot delete a Super Admin.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
