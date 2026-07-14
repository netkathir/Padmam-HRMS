<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee Code numbering is now branch-wise (each branch runs its own
 * independent sequence), so the same code string can legitimately exist in
 * two different branches — the old global unique index on employee_code
 * alone would reject that. Uniqueness moves to the (branch_id, employee_code)
 * pair instead, which is what every existing lookup already assumes in
 * practice (biometric upload matching and report/search filters all scope
 * by branch_id before touching employee_code).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_employee_code_unique');
            $table->unique(['branch_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'employee_code']);
            $table->unique('employee_code');
        });
    }
};
