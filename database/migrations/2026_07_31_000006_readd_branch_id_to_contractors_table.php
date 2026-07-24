<?php
// Purpose: Reverses 2026_07_24_000004_remove_branch_and_company_name_from_contractors_table's
// removal of contractors.branch_id — per the system-wide branch-level
// uniqueness requirement, Contractor becomes per-branch again (two
// different branches may legitimately create a contractor with the same
// name/code). Only branch_id is restored, not the old contractor_branches
// multi-branch pivot or company_name — those were a separate, unrelated FSD
// concept and were not asked for here.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
        });

        $firstActiveBranchId = DB::table('branches')->where('is_active', true)->orderBy('id')->value('id');
        if ($firstActiveBranchId) {
            DB::table('contractors')->whereNull('branch_id')->update(['branch_id' => $firstActiveBranchId]);
        }

        Schema::table('contractors', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // Existing global unique indexes on name/code must go — two
        // different branches may now legitimately share either value.
        // Schema::getIndexes() (not a raw SHOW INDEX query) so this runs on
        // both MySQL (production) and SQLite (the in-memory test suite).
        $indexes = collect(Schema::getIndexes('contractors'))->pluck('name');
        if ($indexes->contains('contractors_name_unique')) {
            Schema::table('contractors', fn (Blueprint $table) => $table->dropUnique('contractors_name_unique'));
        }
        if ($indexes->contains('contractors_code_unique')) {
            Schema::table('contractors', fn (Blueprint $table) => $table->dropUnique('contractors_code_unique'));
        }

        Schema::table('contractors', function (Blueprint $table) {
            $table->unique(['branch_id', 'name']);
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'name']);
            $table->dropUnique(['branch_id', 'code']);
            $table->unique('name');
            $table->unique('code');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
