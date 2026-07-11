<?php
// File: database/migrations/2026_07_11_000003_add_action_flags_to_role_permissions_table.php
// Purpose: Consolidation — the fine-grained action permissions (Approve/Process/
//          Export Excel/Export PDF/View Sensitive Data/Manage Users) the Branch
//          Administration spec needs live as extra pivot columns on the existing
//          role_permissions table, layered onto whichever module.access_level
//          permission a role already holds — no separate permission table.
// Author: System
// Date: 2026-07-11

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->boolean('can_approve')->default(false)->after('permission_id');
            $table->boolean('can_process')->default(false)->after('can_approve');
            $table->boolean('can_export_excel')->default(false)->after('can_process');
            $table->boolean('can_export_pdf')->default(false)->after('can_export_excel');
            $table->boolean('can_view_sensitive')->default(false)->after('can_export_pdf');
            $table->boolean('can_manage_users')->default(false)->after('can_view_sensitive');
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropColumn([
                'can_approve', 'can_process', 'can_export_excel',
                'can_export_pdf', 'can_view_sensitive', 'can_manage_users',
            ]);
        });
    }
};
