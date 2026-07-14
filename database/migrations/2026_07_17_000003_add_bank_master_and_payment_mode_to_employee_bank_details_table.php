<?php
// File: database/migrations/2026_07_17_000003_add_bank_master_and_payment_mode_to_employee_bank_details_table.php
// Purpose: Module 6 FSD 10.3.5 — "Payment Mode" (Bank Transfer/Cash/Cheque)
//          and integrating the Module 3 Bank Master. `bank_name` stays as
//          free text for legacy/manual entry (mirrors how Module 5 kept
//          Contractor's single `branch_id` alongside its new pivot) —
//          `bank_id` is the new preferred path when the bank exists in the
//          master list.
// Author: System
// Date: 2026-07-17

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_bank_details', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_bank_details', 'bank_id')) {
                $table->unsignedSmallInteger('bank_id')->nullable()->after('bank_name');
                $table->foreign('bank_id')->references('id')->on('banks')->nullOnDelete();
            }
            if (! Schema::hasColumn('employee_bank_details', 'payment_mode')) {
                $table->enum('payment_mode', ['bank_transfer', 'cash', 'cheque'])->default('bank_transfer')->after('employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_bank_details', function (Blueprint $table) {
            if (Schema::hasColumn('employee_bank_details', 'payment_mode')) {
                $table->dropColumn('payment_mode');
            }
            if (Schema::hasColumn('employee_bank_details', 'bank_id')) {
                $table->dropForeign(['bank_id']);
                $table->dropColumn('bank_id');
            }
        });
    }
};
