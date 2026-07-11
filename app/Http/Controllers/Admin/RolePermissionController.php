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
            ->filter(fn($module) => $grouped->has($module))
            ->mapWithKeys(fn($module) => [$module => $grouped[$module]]);

        $topPermissionPerModule = $role->permissions
            ->groupBy('module')
            ->map(function ($permissions) {
                $hierarchy = ['full' => 3, 'create' => 2, 'read' => 1];
                return $permissions->sortByDesc(fn($permission) => $hierarchy[$permission->access_level] ?? 0)->first();
            });

        $assigned = $topPermissionPerModule->map(fn($permission) => $permission->id)->toArray();

        // Branch Administration — the 6 fine-grained action flags live on the
        // role_permissions pivot row for whichever permission is currently
        // assigned per module, so the grid can pre-check them.
        $actionFlags = ['can_approve', 'can_process', 'can_export_excel', 'can_export_pdf', 'can_view_sensitive', 'can_manage_users'];
        $assignedFlags = $topPermissionPerModule->map(function ($permission) use ($actionFlags) {
            return collect($actionFlags)->mapWithKeys(fn($flag) => [$flag => (bool) $permission->pivot->$flag]);
        })->toArray();

        return view('admin.role-permissions.assign', compact('role', 'grouped', 'assigned', 'assignedFlags', 'menuModules'));
    }

    public function update(Request $request, Role $role)
    {
        $permissionSelections = $request->input('permissions', []);
        $actionFlags = ['can_approve', 'can_process', 'can_export_excel', 'can_export_pdf', 'can_view_sensitive', 'can_manage_users'];

        // Branch Administration — the fine-grained action checkboxes for a
        // module apply to whichever access-level permission ends up synced
        // for that module, keyed here by module name before we know the
        // final permission id, then remapped below.
        $flagsByModule = $request->input('flags', []);

        $syncData = [];
        foreach ($permissionSelections as $module => $value) {
            if (! is_numeric($value)) {
                continue;
            }
            $permissionId = (int) $value;
            $moduleFlags = $flagsByModule[$module] ?? [];
            $pivotAttributes = [];
            foreach ($actionFlags as $flag) {
                $pivotAttributes[$flag] = ! empty($moduleFlags[$flag]);
            }
            $syncData[$permissionId] = $pivotAttributes;
        }

        $role->permissions()->sync($syncData);

        return redirect()->route('admin.role-permissions.index')
            ->with('success', "Permissions updated for \"{$role->display_name}\".");
    }
}
