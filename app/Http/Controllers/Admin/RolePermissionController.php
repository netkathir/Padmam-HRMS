<?php
// File: app/Http/Controllers/Admin/RolePermissionController.php
// Purpose: Assign and revoke permissions per role
// Author: System
// Date: 2026-06-30

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->orderBy('id')->get();
        return view('admin.role-permissions.index', compact('roles'));
    }

    public function assign(Role $role)
    {
        // Group all permissions by module; within each module order by access_level
        $grouped  = Permission::orderBy('module')->orderBy('access_level')->get()->groupBy('module');
        $assigned = $role->permissions()->pluck('permissions.id')->toArray();
        return view('admin.role-permissions.assign', compact('role', 'grouped', 'assigned'));
    }

    public function update(Request $request, Role $role)
    {
        $permissionIds = $request->input('permissions', []);
        $role->permissions()->sync($permissionIds);
        return redirect()->route('admin.role-permissions.index')
            ->with('success', "Permissions updated for \"{$role->display_name}\".");
    }
}
