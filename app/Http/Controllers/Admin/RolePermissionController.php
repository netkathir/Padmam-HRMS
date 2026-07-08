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
        // Self-heal: guarantee every module registered in config/menu_modules.php
        // has permission rows before we render the grid, so a newly added sidebar
        // module shows up immediately without a manual reseed.
        Permission::syncModules();

        $menuModules = config('menu_modules');

        $permissions = Permission::whereIn('module', array_keys($menuModules))
            ->orderBy('module')
            ->orderBy('access_level')
            ->get();

        $grouped = $permissions->groupBy('module');
        $grouped = collect($menuModules)
            ->keys()
            ->filter(fn ($module) => $grouped->has($module))
            ->mapWithKeys(fn ($module) => [$module => $grouped[$module]]);

        $assigned = $role->permissions
            ->groupBy('module')
            ->map(function ($permissions) {
                $hierarchy = ['delete' => 4, 'full' => 3, 'create' => 2, 'read' => 1];
                return $permissions->sortByDesc(fn ($permission) => $hierarchy[$permission->access_level] ?? 0)
                    ->first()
                    ->id;
            })
            ->toArray();

        return view('admin.role-permissions.assign', compact('role', 'grouped', 'assigned', 'menuModules'));
    }

    public function update(Request $request, Role $role)
    {
        $permissionSelections = $request->input('permissions', []);

        $permissionIds = collect($permissionSelections)
            ->filter()
            ->map(fn ($value) => is_numeric($value) ? (int) $value : null)
            ->filter()
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);

        return redirect()->route('admin.role-permissions.index')
            ->with('success', "Permissions updated for \"{$role->display_name}\".");
    }
}
