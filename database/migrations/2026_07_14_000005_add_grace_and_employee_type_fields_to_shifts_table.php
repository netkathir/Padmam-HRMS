<?php
// File: database/migrations/2026_07_14_000005_add_grace_and_employee_type_fields_to_shifts_table.php
// Purpose: Module 3 FSD 7.2 — Shift Master requires separate Grace Time for
//          Late Entry and Early Exit (currently one combined `grace_minutes`),
//          and an Employee Type Applicability multi-select (Staff / Company
//          Labour / Contract Labour — stored as a small JSON array, matching
//          the fixed 3-value classification already on employees.primary_employee_type
//          / employees.labour_type, not a new pivot table).
// Author: System
// Date: 2026-07-14

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'grace_late_entry_minutes')) {
                $table->unsignedSmallInteger('grace_late_entry_minutes')->nullable()->after('grace_minutes');
            }
            if (! Schema::hasColumn('shifts', 'grace_early_exit_minutes')) {
                $table->unsignedSmallInteger('grace_early_exit_minutes')->nullable()->after('grace_late_entry_minutes');
            }
            if (! Schema::hasColumn('shifts', 'applicable_employee_types')) {
                $table->json('applicable_employee_types')->nullable()->after('is_overnight');
            }
        });

        // Backfill: existing single grace_minutes value applied to both new
        // late-entry/early-exit columns so behavior is unchanged until an
        // admin explicitly configures them separately.
        if (Schema::hasColumn('shifts', 'grace_minutes')) {
            DB::table('shifts')->whereNull('grace_late_entry_minutes')->update([
                'grace_late_entry_minutes' => DB::raw('grace_minutes'),
                'grace_early_exit_minutes' => DB::raw('grace_minutes'),
            ]);

            // grace_minutes is not referenced by any attendance/payroll
            // calculation (confirmed via codebase search) — only by the Shift
            // CRUD form itself, which is being replaced by the two new fields.
            Schema::table('shifts', function (Blueprint $table) {
                $table->dropColumn('grace_minutes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'grace_minutes')) {
                $table->unsignedTinyInteger('grace_minutes')->default(10)->after('break_minutes');
            }
        });

        DB::table('shifts')->update(['grace_minutes' => DB::raw('COALESCE(grace_late_entry_minutes, 10)')]);

        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'applicable_employee_types')) {
                $table->dropColumn('applicable_employee_types');
            }
            if (Schema::hasColumn('shifts', 'grace_early_exit_minutes')) {
                $table->dropColumn('grace_early_exit_minutes');
            }
            if (Schema::hasColumn('shifts', 'grace_late_entry_minutes')) {
                $table->dropColumn('grace_late_entry_minutes');
            }
        });
    }
};
