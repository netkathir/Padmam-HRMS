<?php
// File: database/migrations/2026_07_23_000002_add_action_flags_round2_to_role_permissions_table.php
// Purpose: Module 11 (FSD 15.2) — 8 more fine-grained action flags on the same
//          role_permissions pivot, following the exact pattern already
//          established by 2026_07_11_000003 (can_approve/can_process/etc.):
//          can_confirm/can_close/can_reopen (Payroll's Confirm/Close/Reopen
//          must have SEPARATE permissions per the FSD, not reuse can_approve/
//          can_process as they do today), can_recalculate, can_modify_rules,
//          can_modify_payroll, can_view_audit_log, can_delete ("Delete
//          permission shall be restricted").
// Author: System
// Date: 2026-07-23

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('role_permissions', 'can_confirm')) {
                $table->boolean('can_confirm')->default(false)->after('can_manage_users');
            }
            if (! Schema::hasColumn('role_permissions', 'can_close')) {
                $table->boolean('can_close')->default(false)->after('can_confirm');
            }
            if (! Schema::hasColumn('role_permissions', 'can_reopen')) {
                $table->boolean('can_reopen')->default(false)->after('can_close');
            }
            if (! Schema::hasColumn('role_permissions', 'can_recalculate')) {
                $table->boolean('can_recalculate')->default(false)->after('can_reopen');
            }
            if (! Schema::hasColumn('role_permissions', 'can_modify_rules')) {
                $table->boolean('can_modify_rules')->default(false)->after('can_recalculate');
            }
            if (! Schema::hasColumn('role_permissions', 'can_modify_payroll')) {
                $table->boolean('can_modify_payroll')->default(false)->after('can_modify_rules');
            }
            if (! Schema::hasColumn('role_permissions', 'can_view_audit_log')) {
                $table->boolean('can_view_audit_log')->default(false)->after('can_modify_payroll');
            }
            if (! Schema::hasColumn('role_permissions', 'can_delete')) {
                $table->boolean('can_delete')->default(false)->after('can_view_audit_log');
            }
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropColumn([
                'can_confirm', 'can_close', 'can_reopen', 'can_recalculate',
                'can_modify_rules', 'can_modify_payroll', 'can_view_audit_log', 'can_delete',
            ]);
        });
    }
};
