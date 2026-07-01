<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Branches (no manager_id FK yet — added after employees)
        Schema::create('branches', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->unsignedInteger('manager_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
        });

        // Departments (no head_id FK yet)
        Schema::create('departments', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('branch_id');
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->unsignedInteger('head_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('branch_id');
            $table->index('is_active');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        // Designations
        Schema::create('designations', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('department_id')->nullable();
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->string('grade', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('department_id');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });

        // Employee Types
        Schema::create('employee_types', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 50);
            $table->string('code', 20)->unique();
            $table->string('description', 200)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Contractors
        Schema::create('contractors', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('license_number', 100)->nullable();
            $table->date('license_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('break_minutes')->default(60);
            $table->unsignedTinyInteger('grace_minutes')->default(10);
            $table->decimal('work_hours', 4, 2)->default(8.00);
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Holidays
        Schema::create('holidays', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('branch_id')->nullable()->comment('NULL = all branches');
            $table->string('name', 100);
            $table->date('date');
            $table->enum('type', ['national', 'regional', 'optional'])->default('national');
            $table->year('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('date');
            $table->index(['year', 'branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });

        // Earnings Components
        Schema::create('earnings_components', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->enum('type', ['fixed', 'percentage', 'formula'])->default('fixed');
            $table->string('calculation_base', 100)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_pf_applicable')->default(false);
            $table->boolean('is_esi_applicable')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Deductions Components
        Schema::create('deductions_components', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->enum('type', ['fixed', 'percentage', 'statutory'])->default('fixed');
            $table->string('calculation_base', 100)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('is_statutory')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Salary Slabs
        Schema::create('salary_slabs', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 100);
            $table->decimal('min_ctc', 12, 2);
            $table->decimal('max_ctc', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_slab_components', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedTinyInteger('salary_slab_id');
            $table->enum('component_type', ['earning', 'deduction']);
            $table->unsignedTinyInteger('component_id');
            $table->enum('value_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->index('salary_slab_id');
            $table->foreign('salary_slab_id')->references('id')->on('salary_slabs')->onDelete('cascade');
        });

        // OT Rates
        Schema::create('ot_rates', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 100);
            $table->unsignedTinyInteger('employee_type_id')->nullable();
            $table->unsignedSmallInteger('department_id')->nullable();
            $table->enum('rate_type', ['hourly_multiplier', 'fixed_per_hour'])->default('hourly_multiplier');
            $table->decimal('multiplier', 4, 2)->default(1.50);
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->decimal('max_ot_hours_day', 4, 2)->default(4.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('employee_type_id')->references('id')->on('employee_types')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });

        // PF & ESI Config
        Schema::create('pf_esi_config', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->date('effective_from');
            $table->decimal('pf_employee_pct', 5, 2)->default(12.00);
            $table->decimal('pf_employer_pct', 5, 2)->default(12.00);
            $table->decimal('pf_wage_ceiling', 10, 2)->default(15000.00);
            $table->decimal('esi_employee_pct', 5, 2)->default(0.75);
            $table->decimal('esi_employer_pct', 5, 2)->default(3.25);
            $table->decimal('esi_wage_ceiling', 10, 2)->default(21000.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('effective_from');
        });

        // Leave Types
        Schema::create('leave_types', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->decimal('days_per_year', 5, 2)->default(0);
            $table->decimal('max_carry_forward', 5, 2)->default(0);
            $table->boolean('is_carry_forward')->default(false);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_half_day_allowed')->default(true);
            $table->enum('gender_specific', ['all', 'male', 'female'])->default('all');
            $table->boolean('requires_document')->default(false);
            $table->unsignedTinyInteger('min_notice_days')->default(0);
            $table->unsignedTinyInteger('max_consecutive_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('pf_esi_config');
        Schema::dropIfExists('ot_rates');
        Schema::dropIfExists('salary_slab_components');
        Schema::dropIfExists('salary_slabs');
        Schema::dropIfExists('deductions_components');
        Schema::dropIfExists('earnings_components');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('contractors');
        Schema::dropIfExists('employee_types');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
    }
};
