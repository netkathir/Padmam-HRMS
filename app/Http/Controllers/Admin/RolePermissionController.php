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
    /**
     * Module 11 (FSD 15.2) — the fine-grained action flags actually consulted
     * somewhere in the app, per module. Only modules/flags listed here get a
     * checkbox on the assignment screen — every flag shown here corresponds
     * to a real, enforced check (BranchAdminPermissions::can(module, flag)),
     * so there is never a checkbox that silently does nothing.
     */
    public const MODULE_ACTION_FLAGS = [
        'payroll' => ['process', 'confirm', 'close', 'reopen', 'modify_payroll', 'delete', 'export_excel', 'export_pdf'],
        'attendance' => ['process', 'recalculate', 'export_excel', 'export_pdf'],
        'rule_engine' => ['modify_rules', 'delete'],
        'employees' => ['view_sensitive', 'delete', 'export_excel', 'export_pdf'],
        'users' => ['manage_users', 'delete'],
        'reports' => ['view_sensitive', 'export_excel', 'export_pdf'],
        'branch_admin_audit_log' => ['view_audit_log'],
    ];

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

        // Branch Management is Super-Admin-only by design (routes/controller
        // enforce this regardless of the permission system) — it is
        // intentionally not assignable here, since granting it to any role
        // would have no effect and only invites confusion.
        $menuModules = collect(config('menu_modules'))->except('masters_branches')->all();

        $permissions = Permission::whereIn('module', array_keys($menuModules))
            ->orderBy('module')
            ->orderBy('access_level')
            ->get();

        $grouped = $permissions->groupBy('module');
        $grouped = collect($menuModules)
            ->keys()
            ->filter(fn($module) => $grouped->has($module))
            ->mapWithKeys(fn($module) => [$module => $grouped[$module]]);

        $hierarchy = ['full' => 3, 'create' => 2, 'read' => 1];
        $topPermissionPerModule = $role->permissions
            ->groupBy('module')
            ->map(fn($permissions) => $permissions->sortByDesc(fn($permission) => $hierarchy[$permission->access_level] ?? 0)->first());

        $assigned = $topPermissionPerModule->map(fn($permission) => $permission->id)->toArray();

        // Module 11 — current flag values per module, keyed the same way the
        // view submits them (flags[module][flag]), so the checkboxes reflect
        // what's actually granted today.
        $flagValues = $topPermissionPerModule->map(function ($permission) {
            return collect(self::MODULE_ACTION_FLAGS[$permission->module] ?? [])
                ->mapWithKeys(fn($flag) => [$flag => (bool) ($permission->pivot->{"can_$flag"} ?? false)]);
        });

        $moduleActionFlags = self::MODULE_ACTION_FLAGS;

        return view('admin.role-permissions.assign', compact('role', 'grouped', 'assigned', 'menuModules', 'flagValues', 'moduleActionFlags'));
    }

    public function update(Request $request, Role $role)
    {
        $permissionSelections = $request->input('permissions', []);
        // Defense in depth against a crafted request: Branch Management can
        // never be assigned to any role, regardless of what the UI submits.
        unset($permissionSelections['masters_branches']);

        // Every fine-grained flag this pivot table carries (Module 11 — 8
        // more added alongside the original 6). Only the subset declared in
        // MODULE_ACTION_FLAGS for a given module is ever shown/submitted by
        // the UI; any flag not applicable to a module simply stays false.
        $allFlags = [
            'approve', 'process', 'export_excel', 'export_pdf', 'view_sensitive', 'manage_users',
            'confirm', 'close', 'reopen', 'recalculate', 'modify_rules', 'modify_payroll', 'view_audit_log', 'delete',
        ];
        $flagInputs = $request->input('flags', []);

        $syncData = [];
        foreach ($permissionSelections as $module => $value) {
            if (! is_numeric($value)) {
                continue;
            }
            $permissionId = (int) $value;
            $applicableFlags = self::MODULE_ACTION_FLAGS[$module] ?? [];
            $pivotAttributes = [];
            foreach ($allFlags as $flag) {
                $pivotAttributes["can_$flag"] = in_array($flag, $applicableFlags, true)
                    && (bool) ($flagInputs[$module][$flag] ?? false);
            }
            $syncData[$permissionId] = $pivotAttributes;
        }

        $role->permissions()->sync($syncData);

        return redirect()->route('admin.role-permissions.index')
            ->with('success', "Permissions updated for \"{$role->display_name}\".");
    }
}
