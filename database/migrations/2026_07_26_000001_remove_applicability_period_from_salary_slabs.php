<?php
// Purpose: Salary Slab Master — "Applicability & Effective Period" is
// removed from the Add/Edit Salary Slab page entirely (only Status stays).
// The Salary Slab becomes the single source of truth for an employee's
// salary structure, so it now also carries its own Basic Salary (the base
// figure Earnings/Deductions Components — see the new
// salary_slab_components table — are calculated against).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            if (Schema::hasColumn('salary_slabs', 'effective_from')) {
                $table->dropColumn('effective_from');
            }
            if (Schema::hasColumn('salary_slabs', 'effective_to')) {
                $table->dropColumn('effective_to');
            }
            if (Schema::hasColumn('salary_slabs', 'applicable_employee_types')) {
                $table->dropColumn('applicable_employee_types');
            }
            if (! Schema::hasColumn('salary_slabs', 'basic_salary')) {
                $table->decimal('basic_salary', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            if (Schema::hasColumn('salary_slabs', 'basic_salary')) {
                $table->dropColumn('basic_salary');
            }
            if (! Schema::hasColumn('salary_slabs', 'applicable_employee_types')) {
                $table->json('applicable_employee_types')->nullable()->after('esi_employer_percentage');
            }
            if (! Schema::hasColumn('salary_slabs', 'effective_from')) {
                $table->date('effective_from')->nullable();
            }
            if (! Schema::hasColumn('salary_slabs', 'effective_to')) {
                $table->date('effective_to')->nullable();
            }
        });
    }
};
