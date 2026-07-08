<?php
// File: database/migrations/2026_07_08_000002_grant_admin_role_default_permissions.php
// Purpose: Routes are now gated per-module via the "permission" middleware
//          instead of the coarse `role:super_admin,admin` check. Previously,
//          any user whose role was literally named "admin" had unconditional
//          access to every admin-only route regardless of what Role
//          Permissions had (or hadn't) granted them. To avoid silently
//          locking out existing admin accounts, this grants the "admin" role
//          every currently-defined permission — but only if it doesn't
//          already have any, so a deliberate prior customization is left
//          untouched.
// Author: System
// Date: 2026-07-08

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Permission::syncModules();

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        if (! $adminRoleId) {
            return;
        }

        $alreadyHasGrants = DB::table('role_permissions')->where('role_id', $adminRoleId)->exists();
        if ($alreadyHasGrants) {
            return;
        }

        $rows = DB::table('permissions')->pluck('id')->map(fn ($permissionId) => [
            'role_id' => $adminRoleId,
            'permission_id' => $permissionId,
        ])->all();

        if ($rows) {
            DB::table('role_permissions')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        // Best-effort only — grants made deliberately after this migration
        // ran are indistinguishable from the ones it created, so nothing is
        // removed on rollback.
    }
};
