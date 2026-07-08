<?php
// File: database/migrations/2026_07_08_000001_split_masters_permission_into_submodules.php
// Purpose: Retire the single coarse "masters" permission module and replace it
//          with one permission module per Masters sub-screen (see
//          config/menu_modules.php), preserving any role grants already made
//          against the old "masters.<level>" permissions.
// Author: System
// Date: 2026-07-08

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SUBMODULES = [
        'masters_branches', 'masters_departments', 'masters_designations', 'masters_employee_types',
        'masters_shifts', 'masters_holidays', 'masters_leave_types', 'masters_salary_slabs',
        'masters_earnings', 'masters_deductions', 'masters_ot_rates', 'masters_pf_esi', 'masters_contractors',
    ];

    private const LEVELS = ['read', 'create', 'full', 'delete'];

    public function up(): void
    {
        $newRows = [];
        foreach (self::SUBMODULES as $module) {
            foreach (self::LEVELS as $level) {
                $newRows[] = [
                    'module' => $module,
                    'access_level' => $level,
                    'name' => "$module.$level",
                    'description' => null,
                ];
            }
        }
        DB::table('permissions')->insertOrIgnore($newRows);

        // Any role holding the old "masters.<level>" permission gets that same
        // level granted on every new sub-module, so existing access is not lost.
        $oldPermissions = DB::table('permissions')->where('module', 'masters')->get();

        foreach ($oldPermissions as $oldPermission) {
            $roleIds = DB::table('role_permissions')
                ->where('permission_id', $oldPermission->id)
                ->pluck('role_id');

            if ($roleIds->isEmpty()) {
                continue;
            }

            $newPermissionIds = DB::table('permissions')
                ->whereIn('module', self::SUBMODULES)
                ->where('access_level', $oldPermission->access_level)
                ->pluck('id');

            $pivotRows = [];
            foreach ($roleIds as $roleId) {
                foreach ($newPermissionIds as $permissionId) {
                    $pivotRows[] = ['role_id' => $roleId, 'permission_id' => $permissionId];
                }
            }
            if ($pivotRows) {
                DB::table('role_permissions')->insertOrIgnore($pivotRows);
            }
        }

        // Cascades and removes the now-superseded role_permissions rows too.
        DB::table('permissions')->where('module', 'masters')->delete();
    }

    public function down(): void
    {
        // Structural rollback only — role grants made against the sub-modules
        // after this migration ran are not reconstructed into the coarse
        // "masters" permission.
        $rows = [];
        foreach (self::LEVELS as $level) {
            $rows[] = [
                'module' => 'masters',
                'access_level' => $level,
                'name' => "masters.$level",
                'description' => null,
            ];
        }
        DB::table('permissions')->insertOrIgnore($rows);

        DB::table('permissions')->whereIn('module', self::SUBMODULES)->delete();
    }
};
