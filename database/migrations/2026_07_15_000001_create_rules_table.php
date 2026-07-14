<?php
// File: database/migrations/2026_07_15_000001_create_rules_table.php
// Purpose: Module 4 FSD 8.2 — Common Rule Header. One row per rule of any
//          category; category-specific fields live in a 1:1 detail table
//          (employee_number_rules, attendance_rules, ...). Applicability
//          (branch/employee-type/labour-type/contractor) is stored as JSON
//          arrays here since it's genuinely multi-select and shared across
//          every category, mirroring the JSON-array convention already used
//          for `shifts.applicable_employee_types` etc. in Module 3.
// Author: System
// Date: 2026-07-15

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rules')) {
            return;
        }

        Schema::create('rules', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 150);
            $table->enum('category', [
                'employee_number', 'attendance', 'weekly_off', 'holiday',
                'lop', 'pf', 'esi', 'tds', 'payroll', 'overtime',
            ]);
            $table->json('branch_ids')->nullable()->comment('NULL/empty = all branches');
            $table->json('employee_types')->nullable()->comment('subset of staff,labour');
            $table->json('labour_types')->nullable()->comment('subset of company_labour,contract_labour');
            $table->json('contractor_ids')->nullable();
            $table->integer('priority')->default(100)->comment('lower = applied first');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'category']);
            $table->index(['category', 'status']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
