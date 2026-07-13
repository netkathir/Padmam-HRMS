<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard FSD — "Overall Dashboard is accessible only to authorized users."
 * The existing `/dashboard` route has never been permission-gated (deliberate
 * historical design — "always accessible to any authenticated user"). Gating
 * it now would silently lock out every account whose role has no `dashboard`
 * permission row (nobody has one today, since there was never a reason to
 * grant it) — so this migration backfills `dashboard.read` onto every
 * pre-existing role first, preserving today's de-facto access, before the
 * route middleware starts enforcing it.
 *
 * Also creates the new `branch_dashboard` module (self-healed from
 * config/menu_modules.php, same idempotent pattern as
 * 2026_07_11_000005_seed_branch_head_and_branch_user_roles.php) and grants it
 * to the roles the FSD implies should see a Branch Dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Self-heal permissions table (covers `branch_dashboard`, added to
        // config/menu_modules.php alongside this migration, plus anything
        // else missing) before referencing permission ids below.
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

        $dashboardReadId = DB::table('permissions')
            ->where('module', 'dashboard')->where('access_level', 'read')->value('id');
        $branchDashboardReadId = DB::table('permissions')
            ->where('module', 'branch_dashboard')->where('access_level', 'read')->value('id');

        // Backfill dashboard.read onto every existing non-super-admin role —
        // Super Admin bypasses all permission checks unconditionally
        // (Gate::before in AppServiceProvider), so it needs no explicit grant.
        if ($dashboardReadId) {
            $roleIds = DB::table('roles')->where('name', '!=', 'super_admin')->pluck('id');
            $now = now();
            $rows = $roleIds->map(fn ($roleId) => [
                'role_id' => $roleId,
                'permission_id' => $dashboardReadId,
                'can_approve' => false,
                'can_process' => false,
                'can_export_excel' => false,
                'can_export_pdf' => false,
                'can_view_sensitive' => false,
                'can_manage_users' => false,
                'created_at' => $now,
            ])->all();

            if ($rows) {
                DB::table('role_permissions')->insertOrIgnore($rows);
            }
        }

        // Grant the new Branch Dashboard to the roles the FSD's audience
        // implies (Branch Head/Branch User, plus the operational admin
        // roles) — Super Admin bypasses as always.
        if ($branchDashboardReadId) {
            $branchDashboardRoleNames = ['branch_head', 'branch_user', 'hr_admin', 'payroll_admin', 'management'];
            $roleIds = DB::table('roles')->whereIn('name', $branchDashboardRoleNames)->pluck('id');
            $now = now();
            $rows = $roleIds->map(fn ($roleId) => [
                'role_id' => $roleId,
                'permission_id' => $branchDashboardReadId,
                'can_approve' => false,
                'can_process' => false,
                'can_export_excel' => false,
                'can_export_pdf' => false,
                'can_view_sensitive' => false,
                'can_manage_users' => false,
                'created_at' => $now,
            ])->all();

            if ($rows) {
                DB::table('role_permissions')->insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        $moduleIds = DB::table('permissions')->where('module', 'branch_dashboard')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $moduleIds)->delete();
        DB::table('permissions')->where('module', 'branch_dashboard')->delete();

        $dashboardReadId = DB::table('permissions')
            ->where('module', 'dashboard')->where('access_level', 'read')->value('id');
        if ($dashboardReadId) {
            DB::table('role_permissions')->where('permission_id', $dashboardReadId)->delete();
        }
    }
};
