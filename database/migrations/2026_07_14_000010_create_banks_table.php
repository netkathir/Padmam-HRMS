<?php
// File: database/migrations/2026_07_14_000010_create_banks_table.php
// Purpose: Module 3 FSD 7.6 — Bank Master. Did not exist at all previously
//          (only free-text bank_name on employee_bank_details/payroll_payments).
//          Mirrors the simplest existing master (EmployeeType) structurally.
// Author: System
// Date: 2026-07-14

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('banks')) {
            return;
        }

        Schema::create('banks', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 100);
            $table->string('code', 20)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
