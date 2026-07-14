<?php
// File: database/migrations/2026_07_20_000002_add_lop_breakdown_fields_to_payroll_records_table.php
// Purpose: Module 8 FSD 12.4 — LOP Review screen needs a per-component
//          breakdown (Unpaid Leave Days / Half-Day LOP / Late-Early-Exit LOP)
//          that `calculateLop()` previously only summed into one total; an
//          "Apply LOP" selection per employee; and a confirmation gate
//          ("before payroll processing") recorded on the record itself.
//          `absent_days`/`lop_days`/`calculated_lop_days`/`lop_override_reason`
//          already exist and are reused as-is (see PayrollController).
// Author: System
// Date: 2026-07-20

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_records', 'unpaid_leave_days')) {
                $table->decimal('unpaid_leave_days', 5, 2)->nullable()->after('calculated_lop_days');
            }
            if (! Schema::hasColumn('payroll_records', 'half_day_lop_days')) {
                $table->decimal('half_day_lop_days', 5, 2)->nullable()->after('unpaid_leave_days');
            }
            if (! Schema::hasColumn('payroll_records', 'late_early_lop_days')) {
                $table->decimal('late_early_lop_days', 5, 2)->nullable()->after('half_day_lop_days');
            }
            if (! Schema::hasColumn('payroll_records', 'lop_applied')) {
                $table->boolean('lop_applied')->default(true)->after('late_early_lop_days');
            }
            if (! Schema::hasColumn('payroll_records', 'lop_confirmed_at')) {
                $table->timestamp('lop_confirmed_at')->nullable()->after('lop_applied');
            }
            if (! Schema::hasColumn('payroll_records', 'lop_confirmed_by')) {
                $table->unsignedInteger('lop_confirmed_by')->nullable()->after('lop_confirmed_at');
                $table->foreign('lop_confirmed_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_records', 'lop_confirmed_by')) {
                $table->dropForeign(['lop_confirmed_by']);
                $table->dropColumn('lop_confirmed_by');
            }
            foreach (['lop_confirmed_at', 'lop_applied', 'late_early_lop_days', 'half_day_lop_days', 'unpaid_leave_days'] as $col) {
                if (Schema::hasColumn('payroll_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
