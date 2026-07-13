<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Business requirement: "Allow Branch Heads to create users" — a Branch Head
 * must be able to reach the existing System Admin > Users screen and manage
 * accounts within their own branch (enforced by BranchScope/UserController),
 * but until now their role carried no `users` module permission at all, so
 * the `permission:users.read` route middleware blocked them before the
 * controller's own branch/role restrictions ever ran.
 *
 * Grants the branch_head role full-level access to the `users` module plus
 * the `can_manage_users` action flag (consulted by
 * BranchAdminPermissions::canManageUsers()) — branch_user is intentionally
 * left out, matching the spec's "Branch Heads should be able to create
 * users" (not Branch Users).
 */
return new class extends Migration
{
    public function up(): void
    {
        $branchHeadRoleId = DB::table('roles')->where('name', 'branch_head')->value('id');

        if (! $branchHeadRoleId) {
            return;
        }

        // Self-heal (same idempotent logic as the earlier Branch Head/Branch
        // User seed migration) in case this runs against a fresh database
        // before the Permission seeder has populated every module/level row.
        $modules = array_keys(config('menu_modules', []));
        $levels = ['read', 'create', 'full'];
        $expected = count($modules) * count($levels);

        if ($expected > 0 && DB::table('permissions')->whereIn('module', $modules)->count() < $expected) {
            $rows = [];
            foreach ($modules as $module) {
                $label = config("menu_modules.$module.label", ucfirst($module));
                foreach ($levels as $level) {
                    $rows[] = [
                        'module' => $module,
                        'access_level' => $level,
                        'name' => "$module.$level",
                        'description' => "$label — level $level",
                    ];
                }
            }
            DB::table('permissions')->insertOrIgnore($rows);
        }

        $usersPermissionId = DB::table('permissions')
            ->where('module', 'users')
            ->where('access_level', 'full')
            ->value('id');

        if (! $usersPermissionId) {
            return;
        }

        $exists = DB::table('role_permissions')
            ->where('role_id', $branchHeadRoleId)
            ->where('permission_id', $usersPermissionId)
            ->exists();

        if ($exists) {
            DB::table('role_permissions')
                ->where('role_id', $branchHeadRoleId)
                ->where('permission_id', $usersPermissionId)
                ->update(['can_manage_users' => true]);
        } else {
            DB::table('role_permissions')->insert([
                'role_id' => $branchHeadRoleId,
                'permission_id' => $usersPermissionId,
                'can_approve' => false,
                'can_process' => false,
                'can_export_excel' => false,
                'can_export_pdf' => false,
                'can_view_sensitive' => false,
                'can_manage_users' => true,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $branchHeadRoleId = DB::table('roles')->where('name', 'branch_head')->value('id');
        $usersPermissionId = DB::table('permissions')
            ->where('module', 'users')->where('access_level', 'full')->value('id');

        if ($branchHeadRoleId && $usersPermissionId) {
            DB::table('role_permissions')
                ->where('role_id', $branchHeadRoleId)
                ->where('permission_id', $usersPermissionId)
                ->delete();
        }
    }
};
