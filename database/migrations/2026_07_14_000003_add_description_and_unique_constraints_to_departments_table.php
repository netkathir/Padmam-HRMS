<?php
// File: database/migrations/2026_07_14_000003_add_description_and_unique_constraints_to_departments_table.php
// Purpose: Module 3 FSD 7.1 — Department Master requires Description (optional),
//          a mandatory+unique Department Code, and Department Name unique within
//          its branch. `code` previously had no uniqueness constraint at all, so
//          existing NULL/duplicate codes are backfilled before the constraint is
//          added — keeps this safe/idempotent against live data.
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
        if (! Schema::hasColumn('departments', 'description')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->text('description')->nullable()->after('code');
            });
        }

        // Backfill any NULL or duplicate codes with a deterministic unique
        // value before the column is made mandatory + unique.
        $rows = DB::table('departments')->select('id', 'code')->orderBy('id')->get();
        $seen = [];
        foreach ($rows as $row) {
            $code = $row->code;
            if ($code === null || $code === '' || isset($seen[$code])) {
                $code = 'DEPT-' . $row->id;
            }
            $seen[$code] = true;
            if ($code !== $row->code) {
                DB::table('departments')->where('id', $row->id)->update(['code' => $code]);
            }
        }

        if (Schema::hasColumn('departments', 'code')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('code', 20)->nullable(false)->change();
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM departments'))->pluck('Key_name');
        if (! $indexes->contains('departments_code_unique')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->unique('code');
            });
        }
        if (! $indexes->contains('departments_branch_id_name_unique')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->unique(['branch_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_branch_id_name_unique');
            $table->dropUnique('departments_code_unique');
            $table->string('code', 20)->nullable()->change();
            if (Schema::hasColumn('departments', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
