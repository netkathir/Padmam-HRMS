<?php
// File: database/migrations/2026_07_17_000004_fix_employee_documents_table_columns.php
// Purpose: Module 6 FSD 10.9 — `employee_documents` was broken: the model
//          declares `document_number`/`expiry_date`/`is_verified` as
//          fillable/cast, but none of these three columns exist in the
//          table, and `uploadDocument()` never populates the NOT-NULL
//          `document_name`/`file_size`/`file_type`/`uploaded_by` columns —
//          any real upload attempt fails with a SQL error today. Fixed by
//          adding the missing columns; the controller fix (populating
//          document_name/file_size/file_type/uploaded_by from the uploaded
//          file) is the other half of this fix, in EmployeeController.
// Author: System
// Date: 2026-07-17

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_documents', 'document_number')) {
                $table->string('document_number', 100)->nullable()->after('document_name');
            }
            if (! Schema::hasColumn('employee_documents', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('document_number');
            }
            if (! Schema::hasColumn('employee_documents', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            foreach (['is_verified', 'expiry_date', 'document_number'] as $col) {
                if (Schema::hasColumn('employee_documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
