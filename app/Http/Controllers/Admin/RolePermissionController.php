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

        return view('admin.role-permissions.assign', compact('role', 'grouped', 'assigned', 'menuModules'));
    }

    public function update(Request $request, Role $role)
    {
        $permissionSelections = $request->input('permissions', []);

        // Branch Administration action flags (Approve/Process/Export Excel/
        // Export PDF/View Sensitive Data/Manage Users) no longer have UI
        // controls on this page — carry over each module's existing flag
        // values from the database instead of resetting them to false, since
        // the request no longer submits them. Enforcement of these flags
        // elsewhere in the app is unaffected.
        $actionFlags = ['can_approve', 'can_process', 'can_export_excel', 'can_export_pdf', 'can_view_sensitive', 'can_manage_users'];
        $hierarchy = ['full' => 3, 'create' => 2, 'read' => 1];
        $existingFlagsByModule = $role->permissions
            ->groupBy('module')
            ->map(function ($permissions) use ($actionFlags, $hierarchy) {
                $top = $permissions->sortByDesc(fn($permission) => $hierarchy[$permission->access_level] ?? 0)->first();
                return collect($actionFlags)->mapWithKeys(fn($flag) => [$flag => (bool) $top->pivot->$flag]);
            });

        $syncData = [];
        foreach ($permissionSelections as $module => $value) {
            if (! is_numeric($value)) {
                continue;
            }
            $permissionId = (int) $value;
            $moduleFlags = $existingFlagsByModule->get($module);
            $pivotAttributes = [];
            foreach ($actionFlags as $flag) {
                $pivotAttributes[$flag] = (bool) ($moduleFlags[$flag] ?? false);
            }
            $syncData[$permissionId] = $pivotAttributes;
        }

        $role->permissions()->sync($syncData);

        return redirect()->route('admin.role-permissions.index')
            ->with('success', "Permissions updated for \"{$role->display_name}\".");
    }
}
