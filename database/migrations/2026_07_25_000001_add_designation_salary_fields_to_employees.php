<?php
// Purpose: Employee Master — "Designation & Salary" section (renamed from
// "Salary Structure") adds its own Employee Category (Company/Contractor) +
// Type (dynamic per category) + Contractor Name classification, distinct
// from the existing Tab 1 "Employee Classification" fields (which stay
// untouched) — these three are purpose-built for this section only.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'designation_employee_category')) {
                $table->enum('designation_employee_category', ['company', 'contractor'])->nullable()->after('designation_id');
            }
            if (! Schema::hasColumn('employees', 'designation_employee_type')) {
                $table->enum('designation_employee_type', ['staff', 'labor', 'contractor_staff', 'contractor_labor'])->nullable()->after('designation_employee_category');
            }
            if (! Schema::hasColumn('employees', 'designation_contractor_id')) {
                // contractors.id is smallint unsigned (not the bigint default
                // foreignId() assumes), so this matches it explicitly.
                $table->unsignedSmallInteger('designation_contractor_id')->nullable()->after('designation_employee_type');
                $table->foreign('designation_contractor_id')->references('id')->on('contractors')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'designation_contractor_id')) {
                $table->dropForeign(['designation_contractor_id']);
                $table->dropColumn('designation_contractor_id');
            }
            if (Schema::hasColumn('employees', 'designation_employee_type')) {
                $table->dropColumn('designation_employee_type');
            }
            if (Schema::hasColumn('employees', 'designation_employee_category')) {
                $table->dropColumn('designation_employee_category');
            }
        });
    }
};
