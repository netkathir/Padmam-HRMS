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

        $now = now();

        // Branch Head — full operational access to every explicitly
        // branch-scoped module named in the spec. Excludes Branch
        // Management, Branch Switcher, Users/Roles/Permissions, Settings,
        // and org-wide policy masters (Employee Types/Shifts/Leave
        // Types/Salary Slabs/Earnings/Deductions/OT Rates/PF&ESI) — those
        // remain Super-Admin-only.
        $branchHeadRoleId = DB::table('roles')->where('name', 'branch_head')->value('id');
        if (! $branchHeadRoleId) {
            $branchHeadRoleId = DB::table('roles')->insertGetId([
                'name' => 'branch_head',
                'display_name' => 'Branch Head',
                'description' => 'Full operational access to all modules within the assigned branch only.',
                'is_active' => true,
                'role_code' => 'BRANCH_HEAD',
                'applicable_user_types' => json_encode(['branch_head']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $branchHeadModules = [
            'dashboard', 'employees', 'attendance', 'leaves', 'payroll',
            'masters_departments', 'masters_designations', 'masters_holidays', 'masters_contractors',
            'reports',
        ];
        $this->grant($branchHeadRoleId, $branchHeadModules, 'full');

        // Branch User — day-to-day operational staff within the branch;
        // read access across the same modules plus create on attendance/
        // leaves so they can mark attendance and apply for leave.
        $branchUserRoleId = DB::table('roles')->where('name', 'branch_user')->value('id');
        if (! $branchUserRoleId) {
            $branchUserRoleId = DB::table('roles')->insertGetId([
                'name' => 'branch_user',
                'display_name' => 'Branch User',
                'description' => 'Read-level access to branch operational modules, plus attendance/leave self-service.',
                'is_active' => true,
                'role_code' => 'BRANCH_USER',
                'applicable_user_types' => json_encode(['branch_user']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->grant($branchUserRoleId, ['dashboard', 'employees', 'attendance', 'leaves', 'payroll', 'reports'], 'read');
        $this->grant($branchUserRoleId, ['attendance', 'leaves'], 'create');

        // Backfill: any existing branch_head/branch_user account still
        // sitting on the bare "employee" role (2 modules only) gets moved to
        // its proper role so the fix takes effect immediately, without
        // touching accounts that were deliberately assigned a richer role
        // (e.g. admin/hr doubling as a Branch Head).
        $employeeRoleId = DB::table('roles')->where('name', 'employee')->value('id');
        if ($employeeRoleId) {
            DB::table('users')
                ->where('user_type', 'branch_head')
                ->where('role_id', $employeeRoleId)
                ->update(['role_id' => $branchHeadRoleId, 'updated_at' => $now]);

            DB::table('users')
                ->where('user_type', 'branch_user')
                ->where('role_id', $employeeRoleId)
                ->update(['role_id' => $branchUserRoleId, 'updated_at' => $now]);
        }
    }

    private function grant(int $roleId, array $modules, string $level): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('module', $modules)
            ->where('access_level', $level)
            ->pluck('id', 'module');

        $now = now();
        $rows = [];
        foreach ($permissionIds as $permissionId) {
            $rows[] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'can_approve' => false,
                'can_process' => false,
                'can_export_excel' => false,
                'can_export_pdf' => false,
                'can_view_sensitive' => false,
                'can_manage_users' => false,
                'created_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('role_permissions')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('name', ['branch_head', 'branch_user'])->pluck('id');
        DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('name', ['branch_head', 'branch_user'])->delete();
    }
};
