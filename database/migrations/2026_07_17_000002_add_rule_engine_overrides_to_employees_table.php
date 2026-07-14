<?php
// File: database/migrations/2026_07_17_000002_add_rule_engine_overrides_to_employees_table.php
// Purpose: Module 6 FSD 10.3.3 — "Weekly Off Rule / Attendance Rule / Payroll
//          Rule ... Defaults from Rule Engine" with "Employee-specific
//          overrides shall require proper permission." Adds 3 nullable FKs
//          to Module 4's `rules` table for a per-employee override; when
//          null (the default for every employee), resolution is completely
//          unchanged — BusinessRule::resolveFor() runs exactly as before
//          this migration existed.
// Author: System
// Date: 2026-07-17

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'weekly_off_rule_id')) {
                $table->unsignedSmallInteger('weekly_off_rule_id')->nullable()->after('shift_id');
                $table->foreign('weekly_off_rule_id')->references('id')->on('rules')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'attendance_rule_id')) {
                $table->unsignedSmallInteger('attendance_rule_id')->nullable()->after('weekly_off_rule_id');
                $table->foreign('attendance_rule_id')->references('id')->on('rules')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'payroll_rule_id')) {
                $table->unsignedSmallInteger('payroll_rule_id')->nullable()->after('attendance_rule_id');
                $table->foreign('payroll_rule_id')->references('id')->on('rules')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['payroll_rule_id', 'attendance_rule_id', 'weekly_off_rule_id'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropForeign([$col]);
                    $table->dropColumn($col);
                }
            }
        });
    }
};
