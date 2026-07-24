<?php
// Purpose: earnings_components.code was auto-generated and enforced unique
// only via a global DB constraint. Per the system-wide branch-level
// uniqueness requirement, adds branch_id so each branch runs its own
// independent EC0001... sequence, matching Shift/Department/Contractor.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earnings_components', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
        });

        $firstActiveBranchId = DB::table('branches')->where('is_active', true)->orderBy('id')->value('id');
        if ($firstActiveBranchId) {
            DB::table('earnings_components')->whereNull('branch_id')->update(['branch_id' => $firstActiveBranchId]);
        }

        Schema::table('earnings_components', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // Schema::getIndexes() (not a raw SHOW INDEX query) so this runs on
        // both MySQL (production) and SQLite (the in-memory test suite).
        $indexes = collect(Schema::getIndexes('earnings_components'))->pluck('name');
        if ($indexes->contains('earnings_components_code_unique')) {
            Schema::table('earnings_components', fn (Blueprint $table) => $table->dropUnique('earnings_components_code_unique'));
        }

        Schema::table('earnings_components', function (Blueprint $table) {
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('earnings_components', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'code']);
            $table->unique('code');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
