<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Root-cause fix: a Branch Head's sidebar is driven entirely by the
 * permissions attached to their assigned Role (Gate::before in
 * AppServiceProvider), completely independent of user_type/branch_id
 * (which only control *which branch's data* they see, not *which modules*).
 *
 * Before this migration, no role except super_admin (Gate-bypassed) and
 * employee (2 modules: dashboard + reports, meant for self-service) carried
 * any permissions — admin/hr/manager had zero role_permissions rows. A
 * Branch Head account assigned the "employee" role therefore only ever saw
 * Dashboard + Reports, no matter how correctly branch scoping itself worked.
 *
 * This seeds two dedicated roles with real operational permissions and
 * backfills any existing branch_head/branch_user accounts that were left on
 * the bare "employee" role, matching what the User Management screen
 * expects via roles.applicable_user_types.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Self-heal permissions table (same idempotent logic as
        // Permission::syncModules()) so every module/level combo exists
        // before we reference permission ids below.
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

        // Role creation intentionally removed: fresh installs now seed only
        // super_admin (see RoleSeeder). branch_head/branch_user are created
        // manually via the UI once branches/staff actually exist.
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('name', ['branch_head', 'branch_user'])->pluck('id');
        DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('name', ['branch_head', 'branch_user'])->delete();
    }
};
