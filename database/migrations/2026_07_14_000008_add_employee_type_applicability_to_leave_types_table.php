<?php
// File: database/migrations/2026_07_14_000008_add_employee_type_applicability_to_leave_types_table.php
// Purpose: Module 3 FSD 7.4 — Leave Type Master requires an Employee Type
//          Applicability multi-select (Staff/Company Labour/Contract Labour)
//          restricting which employee types a leave type is available to.
//          Also adds SoftDeletes so a leave type can be preserved for
//          historical attendance/payroll traceability instead of hard-deleted.
// Author: System
// Date: 2026-07-14

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_types', 'applicable_employee_types')) {
                $table->json('applicable_employee_types')->nullable()->after('gender_specific');
            }
            if (! Schema::hasColumn('leave_types', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (Schema::hasColumn('leave_types', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('leave_types', 'applicable_employee_types')) {
                $table->dropColumn('applicable_employee_types');
            }
        });
    }
};
