<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FSD Rule 9 — "Bank details must become mandatory only when Payment Mode
 * is Bank Transfer" — account_holder_name/bank_name/account_number/
 * ifsc_code have always been validated that way (required_if:payment_mode,
 * bank_transfer), but the columns themselves were still NOT NULL, so a Cash
 * or Cheque payment mode submission (which legitimately omits them) threw a
 * raw SQL error at insert time instead of saving successfully.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE employee_bank_details MODIFY account_holder_name VARCHAR(100) NULL');
        DB::statement('ALTER TABLE employee_bank_details MODIFY bank_name VARCHAR(100) NULL');
        DB::statement('ALTER TABLE employee_bank_details MODIFY account_number VARCHAR(50) NULL');
        DB::statement('ALTER TABLE employee_bank_details MODIFY ifsc_code VARCHAR(20) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE employee_bank_details SET account_holder_name = '' WHERE account_holder_name IS NULL");
        DB::statement("UPDATE employee_bank_details SET bank_name = '' WHERE bank_name IS NULL");
        DB::statement("UPDATE employee_bank_details SET account_number = '' WHERE account_number IS NULL");
        DB::statement("UPDATE employee_bank_details SET ifsc_code = '' WHERE ifsc_code IS NULL");
        DB::statement('ALTER TABLE employee_bank_details MODIFY account_holder_name VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE employee_bank_details MODIFY bank_name VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE employee_bank_details MODIFY account_number VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE employee_bank_details MODIFY ifsc_code VARCHAR(20) NOT NULL');
    }
};
