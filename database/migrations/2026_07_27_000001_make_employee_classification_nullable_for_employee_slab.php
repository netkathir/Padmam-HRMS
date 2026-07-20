<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * People Module Update — Create Employee now only collects Personal/Contact/
 * Address Information (Steps 1-3); Employment Information (Department,
 * Designation, Employee Type, Employee Category) is filled in later via the
 * separate Employee Slab module. These columns must therefore be nullable
 * between an employee's creation and their Employee Slab being completed.
 * employee_code is auto-generated FROM employee_category, so it stays null
 * for the same window (uniqueness is on (branch_id, employee_code), which
 * MySQL allows multiple NULLs under).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE employees MODIFY department_id SMALLINT UNSIGNED NULL');
        DB::statement('ALTER TABLE employees MODIFY designation_id SMALLINT UNSIGNED NULL');
        DB::statement('ALTER TABLE employees MODIFY employee_type_id TINYINT UNSIGNED NULL');
        DB::statement('ALTER TABLE employees MODIFY employee_code VARCHAR(20) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE employees MODIFY department_id SMALLINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE employees MODIFY designation_id SMALLINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE employees MODIFY employee_type_id TINYINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE employees MODIFY employee_code VARCHAR(20) NOT NULL');
    }
};
