<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->unsignedTinyInteger('month')->comment('1-12');
            $table->unsignedSmallInteger('year');

            // Working days
            $table->unsignedTinyInteger('working_days')->default(0);
            $table->decimal('present_days', 5, 2)->default(0);
            $table->decimal('absent_days', 5, 2)->default(0);
            $table->decimal('leave_days', 5, 2)->default(0);
            $table->decimal('lop_days', 5, 2)->default(0);
            $table->unsignedTinyInteger('holiday_days')->default(0);
            $table->decimal('ot_hours', 5, 2)->default(0);

            // Earnings
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('da', 12, 2)->default(0);
            $table->decimal('ta', 12, 2)->default(0);
            $table->decimal('medical_allowance', 12, 2)->default(0);
            $table->decimal('special_allowance', 12, 2)->default(0);
            $table->decimal('ot_amount', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);
            $table->decimal('gross_earnings', 12, 2)->default(0);

            // Deductions
            $table->decimal('pf_employee', 12, 2)->default(0);
            $table->decimal('esi_employee', 12, 2)->default(0);
            $table->decimal('tds', 12, 2)->default(0);
            $table->decimal('advance_deduction', 12, 2)->default(0);
            $table->decimal('lop_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            // Employer contributions
            $table->decimal('pf_employer', 12, 2)->default(0);
            $table->decimal('esi_employer', 12, 2)->default(0);

            $table->decimal('net_salary', 12, 2)->default(0);

            $table->enum('status', ['draft','processed','paid','hold'])->default('draft');
            $table->unsignedInteger('generated_by')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
            $table->index(['year', 'month']);
            $table->index('status');
            $table->index('employee_id');

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('payroll_allowances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_id');
            $table->unsignedTinyInteger('earnings_component_id')->nullable();
            $table->string('name', 100);
            $table->decimal('amount', 12, 2);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
            $table->index('payroll_id');
            $table->foreign('payroll_id')->references('id')->on('payroll_records')->onDelete('cascade');
            $table->foreign('earnings_component_id')->references('id')->on('earnings_components')->onDelete('set null');
        });

        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_id');
            $table->unsignedTinyInteger('deductions_component_id')->nullable();
            $table->string('name', 100);
            $table->decimal('amount', 12, 2);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
            $table->index('payroll_id');
            $table->foreign('payroll_id')->references('id')->on('payroll_records')->onDelete('cascade');
            $table->foreign('deductions_component_id')->references('id')->on('deductions_components')->onDelete('set null');
        });

        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_id');
            $table->date('payment_date');
            $table->enum('payment_mode', ['bank_transfer','cash','cheque'])->default('bank_transfer');
            $table->decimal('amount', 12, 2);
            $table->string('reference_number', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('cheque_number', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('processed_by');
            $table->timestamps();
            $table->index('payroll_id');
            $table->index('payment_date');
            $table->foreign('payroll_id')->references('id')->on('payroll_records');
            $table->foreign('processed_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('payroll_deductions');
        Schema::dropIfExists('payroll_allowances');
        Schema::dropIfExists('payroll_records');
    }
};
