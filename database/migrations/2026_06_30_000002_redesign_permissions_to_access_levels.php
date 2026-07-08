<?php
// File: database/migrations/2026_06_30_000002_redesign_permissions_to_access_levels.php
// Purpose: Redesign permissions from granular action-level to 4-level module access control
// Author: System
// Date: 2026-06-30

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function disableForeignKeyChecks(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    private function enableForeignKeyChecks(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function up(): void
    {
        // Clear all existing permission data — will be re-seeded with 4-level system
        // Disable FK checks so child table can be cleared before parent
        $this->disableForeignKeyChecks();
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        $this->enableForeignKeyChecks();

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['module', 'action']);
            $table->renameColumn('action', 'access_level');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unique(['module', 'access_level']);
        });
    }

    public function down(): void
    {
        $this->disableForeignKeyChecks();
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        $this->enableForeignKeyChecks();

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['module', 'access_level']);
            $table->renameColumn('access_level', 'action');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unique(['module', 'action']);
        });
    }
};
