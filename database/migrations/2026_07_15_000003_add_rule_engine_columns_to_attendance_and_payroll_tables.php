<?php
// File: database/migrations/2026_07_15_000003_add_rule_engine_columns_to_attendance_and_payroll_tables.php
// Purpose: Module 4 FSD 8.2 — "Historical payroll and attendance shall
//          retain the rules applied during processing." Adds an
//          `applied_rules` JSON snapshot ({category: rule_id}) to both
//          `attendance` and `payroll_records`, written once at
//          processing/recalculation time so later rule edits never
//          retroactively change a historical record.
//
//          Also FSD 8.6 LOP Rule — "system shall retain calculated LOP and
//          approved payroll LOP separately... Any LOP override shall
//          require a reason." `lop_days` remains the approved/final value
//          used for the deduction; `calculated_lop_days` preserves the
//          system's raw output, and `lop_override_reason` is required
//          whenever the two differ.
// Author: System
// Date: 2026-07-15

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance', 'applied_rules')) {
                $table->json('applied_rules')->nullable()->after('remarks');
            }
        });

        Schema::table('payroll_records', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_records', 'applied_rules')) {
                $table->json('applied_rules')->nullable()->after('generated_at');
            }
            if (! Schema::hasColumn('payroll_records', 'calculated_lop_days')) {
                $table->decimal('calculated_lop_days', 5, 2)->nullable()->after('lop_days');
            }
            if (! Schema::hasColumn('payroll_records', 'lop_override_reason')) {
                $table->text('lop_override_reason')->nullable()->after('calculated_lop_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_records', 'lop_override_reason')) {
                $table->dropColumn('lop_override_reason');
            }
            if (Schema::hasColumn('payroll_records', 'calculated_lop_days')) {
                $table->dropColumn('calculated_lop_days');
            }
            if (Schema::hasColumn('payroll_records', 'applied_rules')) {
                $table->dropColumn('applied_rules');
            }
        });

        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'applied_rules')) {
                $table->dropColumn('applied_rules');
            }
        });
    }
};
