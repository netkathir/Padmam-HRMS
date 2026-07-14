<?php
// File: database/migrations/2026_07_15_000002_create_rule_detail_tables.php
// Purpose: Module 4 FSD 8.3-8.10 — one 1:1 detail table per rule category,
//          holding only the category-specific fields (Branch/Employee-Type
//          Applicability and Effective From/To live once on the shared
//          `rules` header per FSD 8.1/8.2, not duplicated here). Grouped in
//          one migration file, mirroring this codebase's own convention of
//          creating several related tables together (see
//          2024_01_01_000003_create_masters_tables.php).
// Author: System
// Date: 2026-07-15

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 8.3 Employee Number Rule
        if (! Schema::hasTable('employee_number_rules')) {
            Schema::create('employee_number_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->enum('employee_category', ['staff', 'company_labour', 'contract_labour']);
                $table->unsignedSmallInteger('branch_id')->nullable();
                $table->unsignedSmallInteger('contractor_id')->nullable();
                $table->string('prefix', 20)->nullable();
                $table->boolean('include_branch_code')->default(false);
                $table->boolean('include_contractor_code')->default(false);
                $table->string('separator', 5)->nullable()->default('-');
                $table->unsignedInteger('sequence_start')->default(1);
                $table->unsignedTinyInteger('sequence_length')->default(4);
                $table->boolean('include_financial_year')->default(false);
                $table->boolean('include_calendar_year')->default(false);
                $table->enum('reset_frequency', ['never', 'yearly', 'financial_yearly', 'branch_wise'])->default('never');
                $table->boolean('allow_manual_override')->default(true);
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('contractor_id')->references('id')->on('contractors')->nullOnDelete();
            });
        }

        // 8.4 Attendance Rule
        if (! Schema::hasTable('attendance_rules')) {
            Schema::create('attendance_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->json('shift_ids')->nullable()->comment('NULL/empty = all shifts');
                $table->decimal('min_full_day_hours', 4, 2);
                $table->decimal('min_half_day_hours', 4, 2);
                $table->unsignedSmallInteger('late_grace_minutes')->default(0);
                $table->unsignedSmallInteger('early_exit_grace_minutes')->default(0);
                $table->enum('missing_punch_treatment', ['absent', 'half_day', 'pending_review']);
                $table->enum('single_punch_treatment', ['absent', 'half_day', 'pending_review']);
                $table->string('multiple_punch_handling', 30)->default('first_in_last_out');
                $table->enum('weekly_off_treatment', ['paid', 'unpaid', 'conditional']);
                $table->enum('holiday_treatment', ['paid', 'unpaid', 'conditional']);
                $table->enum('work_on_holiday_treatment', ['overtime', 'compensatory_off', 'normal_day']);
                $table->enum('work_on_weekly_off_treatment', ['overtime', 'compensatory_off', 'normal_day']);
                $table->unsignedTinyInteger('consecutive_absence_rule')->nullable();
                $table->unsignedTinyInteger('rounding_minutes')->default(0)->comment('0 = no rounding');
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
            });
        }

        // 8.5 Weekly Off and Sunday Rule
        if (! Schema::hasTable('weekly_off_rules')) {
            Schema::create('weekly_off_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->json('weekly_off_days');
                $table->boolean('is_paid')->default(true);
                $table->unsignedTinyInteger('min_attendance_condition')->nullable();
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
            });
        }

        // 8.6 LOP Rule
        if (! Schema::hasTable('lop_rules')) {
            Schema::create('lop_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->enum('calculation_basis', ['calendar_days', 'working_days', 'fixed_days']);
                $table->unsignedTinyInteger('fixed_payroll_days')->nullable();
                $table->decimal('half_day_lop_value', 3, 2)->default(0.5);
                $table->decimal('full_day_lop_value', 3, 2)->default(1.0);
                $table->boolean('unpaid_leave_as_lop')->default(true);
                $table->boolean('absent_day_as_lop')->default(true);
                $table->boolean('missing_punch_as_lop')->default(true);
                $table->unsignedTinyInteger('late_count_conversion')->nullable();
                $table->unsignedTinyInteger('early_exit_conversion')->nullable();
                $table->boolean('holiday_between_absences')->default(false);
                $table->boolean('weekly_off_between_absences')->default(false);
                $table->boolean('manual_lop_adjustment_allowed')->default(true);
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
            });
        }

        // 8.7 PF Rule
        if (! Schema::hasTable('pf_rules')) {
            Schema::create('pf_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->boolean('pf_applicable')->default(true);
                $table->decimal('salary_slab_from', 12, 2)->default(0);
                $table->decimal('salary_slab_to', 12, 2)->nullable();
                $table->json('pf_wage_components')->nullable()->comment('earnings_components ids');
                $table->decimal('employee_pf_percentage', 5, 2);
                $table->decimal('employer_pf_percentage', 5, 2);
                $table->decimal('pf_wage_ceiling', 12, 2)->nullable();
                $table->boolean('restrict_to_wage_ceiling')->default(true);
                $table->boolean('voluntary_pf_allowed')->default(false);
                $table->enum('rounding_method', ['nearest', 'up', 'down'])->default('nearest');
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
            });
        }

        // 8.8 ESI Rule
        if (! Schema::hasTable('esi_rules')) {
            Schema::create('esi_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->boolean('esi_applicable')->default(true);
                $table->decimal('salary_slab_from', 12, 2)->default(0);
                $table->decimal('salary_slab_to', 12, 2)->nullable();
                $table->json('esi_wage_components')->nullable()->comment('earnings_components ids');
                $table->decimal('employee_esi_percentage', 5, 2);
                $table->decimal('employer_esi_percentage', 5, 2);
                $table->enum('rounding_method', ['nearest', 'up', 'down'])->default('nearest');
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
            });
        }

        // 8.9 TDS Rule
        if (! Schema::hasTable('tds_rules')) {
            Schema::create('tds_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->boolean('tds_applicable')->default(true);
                $table->decimal('salary_slab_from', 12, 2)->default(0);
                $table->decimal('salary_slab_to', 12, 2)->nullable();
                $table->decimal('tds_percentage', 5, 2);
                $table->enum('calculation_basis', ['monthly_gross', 'annual_estimated_income', 'taxable_income']);
                $table->json('taxable_components')->nullable()->comment('earnings_components ids');
                $table->json('exempt_components')->nullable()->comment('earnings_components ids');
                $table->boolean('fixed_tds_amount_allowed')->default(false);
                $table->enum('rounding_method', ['nearest', 'up', 'down'])->default('nearest');
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
            });
        }

        // 8.10 Overtime Rule
        if (! Schema::hasTable('overtime_rules')) {
            Schema::create('overtime_rules', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->unsignedSmallInteger('rule_id')->unique();
                $table->boolean('overtime_applicable')->default(true);
                $table->unsignedSmallInteger('minimum_overtime_minutes')->nullable();
                $table->enum('overtime_calculation', ['hourly_rate', 'fixed_rate', 'salary_formula'])->nullable();
                $table->decimal('overtime_rate', 6, 2)->nullable();
                $table->unsignedTinyInteger('overtime_rounding_minutes')->default(0)->comment('0 = no rounding');
                $table->unsignedSmallInteger('maximum_overtime_per_day_minutes')->nullable();
                $table->boolean('approval_required')->default(true);
                $table->decimal('weekly_off_overtime_rate', 6, 2)->nullable();
                $table->decimal('holiday_overtime_rate', 6, 2)->nullable();
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
            });
        }

        // Employee Number sequence tracking — guarantees a sequence never
        // reuses a number after employee deletion/inactivation, since it
        // only ever increments regardless of what happens to employees.
        if (! Schema::hasTable('rule_sequence_counters')) {
            Schema::create('rule_sequence_counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('rule_id');
                $table->string('scope_key', 100)->comment('e.g. branch:2:FY2026-27');
                $table->unsignedInteger('last_sequence')->default(0);
                $table->timestamps();

                $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
                $table->unique(['rule_id', 'scope_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_sequence_counters');
        Schema::dropIfExists('overtime_rules');
        Schema::dropIfExists('tds_rules');
        Schema::dropIfExists('esi_rules');
        Schema::dropIfExists('pf_rules');
        Schema::dropIfExists('lop_rules');
        Schema::dropIfExists('weekly_off_rules');
        Schema::dropIfExists('attendance_rules');
        Schema::dropIfExists('employee_number_rules');
    }
};
