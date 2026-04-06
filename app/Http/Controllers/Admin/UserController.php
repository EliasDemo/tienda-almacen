<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles.permissions')->orderBy('name')->get();
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|exists:roles,name',
        ]);

        /** @var User $user */
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return back()->with('success', "Usuario '{$user->name}' creado con rol '{$request->role}'.");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role'     => 'required|exists:roles,name',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);

        return back()->with('success', "Usuario '{$user->name}' actualizado.");
    }

    public function toggleActive(Request $request, User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'No puedes desactivarte a ti mismo.');
        }

        $user->update(['is_active' => !($user->is_active ?? true)]);
        $status = ($user->is_active ?? true) ? 'activado' : 'desactivado';

        return back()->with('success', "Usuario '{$user->name}' {$status}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:6']);

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', "Contraseña de '{$user->name}' actualizada.");
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($request->input('permissions', []));

        return back()->with('success', "Permisos del rol '{$role->name}' actualizados.");
    }
}