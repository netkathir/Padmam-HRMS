<?php
// File: database/migrations/2026_07_17_000001_add_fsd_fields_to_employees_table.php
// Purpose: Module 6 FSD 10.2-10.3.2, 10.3.3, 10.3.4, 10.8 — Employee
//          Classification, Personal, Contact, Employment, Statutory, and
//          Contract Labour fields not yet on `employees`. All nullable at
//          the DB level (no uniqueness/backfill concerns beyond what's
//          handled explicitly below) — "required" is enforced at the
//          validation layer going forward, same rationale as Module 5's
//          contractor fields: no guessing at unknown real production values.
// Author: System
// Date: 2026-07-17

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // 10.3.1 Personal Information
            if (! Schema::hasColumn('employees', 'middle_name')) {
                $table->string('middle_name', 100)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('employees', 'display_name')) {
                $table->string('display_name', 200)->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('employees', 'father_spouse_name')) {
                $table->string('father_spouse_name', 150)->nullable()->after('religion');
            }

            // 10.2 Classification
            if (! Schema::hasColumn('employees', 'biometric_id')) {
                $table->string('biometric_id', 50)->nullable()->after('employee_code');
            }

            // 10.3.2 Contact Information — current address gains district;
            // a full parallel permanent-address block is added.
            if (! Schema::hasColumn('employees', 'district')) {
                $table->string('district', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('employees', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship', 50)->nullable()->after('emergency_contact_phone');
            }
            if (! Schema::hasColumn('employees', 'permanent_address_line1')) {
                $table->string('permanent_address_line1', 200)->nullable()->after('pincode');
            }
            if (! Schema::hasColumn('employees', 'permanent_address_line2')) {
                $table->string('permanent_address_line2', 200)->nullable()->after('permanent_address_line1');
            }
            if (! Schema::hasColumn('employees', 'permanent_city')) {
                $table->string('permanent_city', 100)->nullable()->after('permanent_address_line2');
            }
            if (! Schema::hasColumn('employees', 'permanent_district')) {
                $table->string('permanent_district', 100)->nullable()->after('permanent_city');
            }
            if (! Schema::hasColumn('employees', 'permanent_state')) {
                $table->string('permanent_state', 100)->nullable()->after('permanent_district');
            }
            if (! Schema::hasColumn('employees', 'permanent_pincode')) {
                $table->string('permanent_pincode', 10)->nullable()->after('permanent_state');
            }

            // 10.3.3 Employment Information
            if (! Schema::hasColumn('employees', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('probation_end_date');
            }
            if (! Schema::hasColumn('employees', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }

            // 10.3.4 Identity & Statutory — PF account number, distinct from UAN
            if (! Schema::hasColumn('employees', 'pf_number')) {
                $table->string('pf_number', 30)->nullable()->after('uan_number');
            }

            // 10.8 Contract Labour Information (fields beyond the existing contractor_id)
            if (! Schema::hasColumn('employees', 'contractor_employee_number')) {
                $table->string('contractor_employee_number', 50)->nullable()->after('contractor_id');
            }
            if (! Schema::hasColumn('employees', 'work_order_number')) {
                $table->string('work_order_number', 50)->nullable()->after('contractor_employee_number');
            }
            if (! Schema::hasColumn('employees', 'labour_category')) {
                $table->string('labour_category', 50)->nullable()->after('work_order_number');
            }
            if (! Schema::hasColumn('employees', 'contractor_rate')) {
                $table->decimal('contractor_rate', 10, 2)->nullable()->after('labour_category');
            }
            if (! Schema::hasColumn('employees', 'contractor_remarks')) {
                $table->text('contractor_remarks')->nullable()->after('contractor_rate');
            }
        });

        // Backfill display_name for existing rows so the field is populated
        // going forward without breaking historical records.
        DB::table('employees')->whereNull('display_name')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $display = trim($row->first_name . ' ' . $row->last_name);
                DB::table('employees')->where('id', $row->id)->update(['display_name' => $display]);
            }
        });

        // Biometric ID uniqueness — see settings 'employee.biometric_id_scope'
        // for how this is enforced (global vs per-branch) at the validation
        // layer; a plain DB unique index here would prevent branch-scoped
        // duplicates from ever being configurable, so the index is
        // deliberately NOT added at the DB level. Application-level
        // enforcement mirrors employee_code's existing pattern.
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach ([
                'contractor_remarks', 'contractor_rate', 'labour_category', 'work_order_number',
                'contractor_employee_number', 'pf_number', 'contract_end_date', 'contract_start_date',
                'permanent_pincode', 'permanent_state', 'permanent_district', 'permanent_city',
                'permanent_address_line2', 'permanent_address_line1', 'emergency_contact_relationship',
                'district', 'biometric_id', 'father_spouse_name', 'display_name', 'middle_name',
            ] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
