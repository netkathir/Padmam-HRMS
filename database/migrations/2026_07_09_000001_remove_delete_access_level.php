<?php
// File: database/migrations/2026_07_09_000001_remove_delete_access_level.php
// Purpose: Retire the "delete" access level — "full" now implies delete, so
//          only read/create/full remain. Removing the permission rows cascades
//          (via the role_permissions FK) and revokes them from every role that
//          held "delete" on some module; those roles already hold "full" or
//          higher-ranked grants where applicable are unaffected.
// Author: System
// Date: 2026-07-09

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->where('access_level', 'delete')->delete();
    }

    public function down(): void
    {
        $modules = DB::table('permissions')->select('module')->distinct()->pluck('module');

        $rows = $modules->map(fn ($module) => [
            'module' => $module,
            'access_level' => 'delete',
            'name' => "$module.delete",
            'description' => null,
        ])->all();

        if ($rows) {
            DB::table('permissions')->insertOrIgnore($rows);
        }
    }
};
