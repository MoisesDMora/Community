<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionController extends Controller
{
    protected $guard = 'web';

    public function index()
    {
        $roles = Role::where('guard_name', $this->guard)->with('permissions')->get();
        $permissions = Permission::where('guard_name', $this->guard)->get();
        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
        ]);
        
        $exists = Permission::where('name', $validated['name'])->where('guard_name', $this->guard)->first();
        if ($exists) {
            return response()->json(['error' => 'Permission already exists'], 422);
        }

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $this->guard
        ]);

        return response()->json($permission);
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'permissions' => 'nullable|array'
        ]);

        $exists = Role::where('name', $validated['name'])->where('guard_name', $this->guard)->first();
        if ($exists) {
            return response()->json(['error' => 'Role already exists'], 422);
        }

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $this->guard
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    public function updateRolePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'permissions' => 'present|array' // allow empty array
        ]);

        $role->syncPermissions($request->permissions);
        return response()->json(['message' => 'Role updated', 'role' => $role->load('permissions')]);
    }

    public function syncUserPermissions(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $request->validate([
            'permissions' => 'present|array' // allow empty array
        ]);

        $user->syncPermissions($request->permissions);
        
        return response()->json([
            'message' => 'User direct permissions updated', 
            'user' => $user->load('permissions')
        ]);
    }

    public function destroyRole($id)
    {
        $role = Role::findOrFail($id);
        if ($role->name === 'master' || $role->name === 'admin') {
             return response()->json(['error' => 'No puedes eliminar roles del sistema'], 403);
        }
        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }

    public function destroyPermission($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        return response()->json(['message' => 'Permission deleted']);
    }
}
