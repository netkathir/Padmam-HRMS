<?php
// File: database/migrations/2026_07_14_000009_add_tds_pf_esi_fields_to_salary_slabs_table.php
// Purpose: Module 3 FSD 7.5 — Salary Slab Master requires TDS/PF/ESI
//          percentages per salary range, Employee Type Applicability,
//          branch-wise scoping, and Effective From/To dates so payroll can
//          auto-select the applicable slab. The existing `salary_slabs`
//          table already models a salary range (min_ctc/max_ctc = "From/To
//          Salary") feeding an earnings/deductions component composition —
//          a distinct, already-working feature — so these are added
//          alongside it rather than replacing/renaming those columns.
// Author: System
// Date: 2026-07-14

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_slabs', 'tds_percentage')) {
                $table->decimal('tds_percentage', 5, 2)->nullable()->after('max_ctc');
            }
            if (! Schema::hasColumn('salary_slabs', 'pf_employee_percentage')) {
                $table->decimal('pf_employee_percentage', 5, 2)->nullable()->after('tds_percentage');
            }
            if (! Schema::hasColumn('salary_slabs', 'pf_employer_percentage')) {
                $table->decimal('pf_employer_percentage', 5, 2)->nullable()->after('pf_employee_percentage');
            }
            if (! Schema::hasColumn('salary_slabs', 'esi_employee_percentage')) {
                $table->decimal('esi_employee_percentage', 5, 2)->nullable()->after('pf_employer_percentage');
            }
            if (! Schema::hasColumn('salary_slabs', 'esi_employer_percentage')) {
                $table->decimal('esi_employer_percentage', 5, 2)->nullable()->after('esi_employee_percentage');
            }
            if (! Schema::hasColumn('salary_slabs', 'applicable_employee_types')) {
                $table->json('applicable_employee_types')->nullable()->after('esi_employer_percentage');
            }
            if (! Schema::hasColumn('salary_slabs', 'branch_id')) {
                $table->unsignedSmallInteger('branch_id')->nullable()->after('applicable_employee_types')
                    ->comment('NULL = all branches');
            }
            if (! Schema::hasColumn('salary_slabs', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('salary_slabs', 'effective_to')) {
                $table->date('effective_to')->nullable()->after('effective_from');
            }
            if (! Schema::hasColumn('salary_slabs', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $indexes = collect(\Illuminate\Support\Facades\DB::select('SHOW INDEX FROM salary_slabs'))->pluck('Key_name');
        if (! $indexes->contains('salary_slabs_branch_id_foreign')) {
            Schema::table('salary_slabs', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            });
        }
        if (! $indexes->contains('salary_slabs_name_unique')) {
            // Disambiguate any pre-existing duplicate names before the
            // unique index is added, so this migration is safe against live data.
            $rows = \Illuminate\Support\Facades\DB::table('salary_slabs')->select('id', 'name')->orderBy('id')->get();
            $seen = [];
            foreach ($rows as $row) {
                $name = $row->name;
                if (isset($seen[$name])) {
                    $name = $name . ' (' . $row->id . ')';
                    \Illuminate\Support\Facades\DB::table('salary_slabs')->where('id', $row->id)->update(['name' => $name]);
                }
                $seen[$name] = true;
            }

            Schema::table('salary_slabs', function (Blueprint $table) {
                $table->unique('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->dropUnique('salary_slabs_name_unique');
            $table->dropForeign('salary_slabs_branch_id_foreign');
            if (Schema::hasColumn('salary_slabs', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            foreach (['effective_to', 'effective_from', 'branch_id', 'applicable_employee_types',
                'esi_employer_percentage', 'esi_employee_percentage', 'pf_employer_percentage',
                'pf_employee_percentage', 'tds_percentage'] as $col) {
                if (Schema::hasColumn('salary_slabs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
