<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission; // <-- IMPORT PERMISSION MODEL

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::with('permissions:id,name')->get()->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'), // Send assigned permissions
            ]),
            'allPermissions' => Permission::all()->pluck('name'), // Send all possible permissions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'required|array', // Expect an array of permissions
            'permissions.*' => 'string|exists:permissions,name', // Validate each item in the array
        ]);

        // Create the role
        $role = Role::create(['name' => $request->name]);

        // Assign the selected permissions to the new role
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return to_route('roles.index')->with('success', 'Role created successfully.');
    }
    public function edit(Role $role)
    {
        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
            'allPermissions' => Permission::all()->pluck('name'),
        ]);
    }
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }
}