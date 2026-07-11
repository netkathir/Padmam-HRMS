<?php
// File: database/migrations/2026_07_11_000002_add_branch_admin_columns_to_roles_table.php
// Purpose: Consolidation — the fine-grained Role fields the Branch Administration
//          spec needs (Role Code, Applicable User Type, Created By) live directly
//          on the existing `roles` table instead of a separate branch_admin_roles
//          table. Additive/nullable — existing roles are unaffected.
// Author: System
// Date: 2026-07-11

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('role_code', 30)->nullable()->unique()->after('name');
            $table->json('applicable_user_types')->nullable()->after('description');
            $table->unsignedInteger('created_by')->nullable()->after('is_active');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['role_code', 'applicable_user_types', 'created_by']);
        });
    }
};
