<?php
// File: database/migrations/2026_07_22_000001_add_lifecycle_fields_to_payroll_records_table.php
// Purpose: Module 9 FSD 13.2/13.4/13.6 — the FSD's Draft/Calculated/Confirmed/
//          Closed lifecycle. `generate()` already produces a fully-calculated
//          record in one atomic step (there is no separate "period exists,
//          nothing calculated yet" state in this system), so `status` widens
//          to add `calculated` (the new target for freshly generated rows —
//          satisfies both "Draft" and "Calculated" per the FSD's own
//          descriptions of those two states) plus the two genuinely new
//          states with real triggers: `confirmed` and `closed`. The existing
//          `draft`/`processed`/`hold` values are KEPT, not removed, so any
//          pre-existing production rows keep displaying/filtering correctly.
//          Also adds the who/when/why columns for Confirm/Close/Reopen (FSD
//          13.6 "reopening... shall be audit logged") and two FSD 13.4/13.6
//          display fields (`employer_cost`, `pro_rated_days`) that have no
//          existing column or accessor.
// Author: System
// Date: 2026-07-22

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $statusColumn = DB::selectOne("SHOW COLUMNS FROM `payroll_records` LIKE 'status'");
        if ($statusColumn && ! str_contains($statusColumn->Type, 'closed')) {
            DB::statement("ALTER TABLE `payroll_records` MODIFY `status` ENUM('draft','processed','paid','hold','calculated','confirmed','closed') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('payroll_records', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_records', 'employer_cost')) {
                $table->decimal('employer_cost', 12, 2)->nullable()->after('esi_employer');
            }
            if (! Schema::hasColumn('payroll_records', 'pro_rated_days')) {
                $table->decimal('pro_rated_days', 5, 2)->nullable()->after('working_days');
            }
            if (! Schema::hasColumn('payroll_records', 'confirmed_by')) {
                $table->unsignedInteger('confirmed_by')->nullable()->after('status');
                $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payroll_records', 'confirmed_at')) {
                $table->dateTime('confirmed_at')->nullable()->after('confirmed_by');
            }
            if (! Schema::hasColumn('payroll_records', 'closed_by')) {
                $table->unsignedInteger('closed_by')->nullable()->after('confirmed_at');
                $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payroll_records', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('closed_by');
            }
            if (! Schema::hasColumn('payroll_records', 'reopened_by')) {
                $table->unsignedInteger('reopened_by')->nullable()->after('closed_at');
                $table->foreign('reopened_by')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payroll_records', 'reopened_at')) {
                $table->dateTime('reopened_at')->nullable()->after('reopened_by');
            }
            if (! Schema::hasColumn('payroll_records', 'reopen_reason')) {
                $table->text('reopen_reason')->nullable()->after('reopened_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            foreach (['confirmed_by', 'closed_by', 'reopened_by'] as $fk) {
                if (Schema::hasColumn('payroll_records', $fk)) {
                    $table->dropForeign([$fk]);
                }
            }
            foreach ([
                'reopen_reason', 'reopened_at', 'reopened_by', 'closed_at', 'closed_by',
                'confirmed_at', 'confirmed_by', 'pro_rated_days', 'employer_cost',
            ] as $col) {
                if (Schema::hasColumn('payroll_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
