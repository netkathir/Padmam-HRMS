<?php
// Purpose: leave_types.name/code were globally unique at the DB level with
// no branch scoping. Per the system-wide branch-level uniqueness
// requirement, adds branch_id so two different branches may register a
// leave type with the same name/code, matching Department/Shift/Contractor.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
        });

        $firstActiveBranchId = DB::table('branches')->where('is_active', true)->orderBy('id')->value('id');
        if ($firstActiveBranchId) {
            DB::table('leave_types')->whereNull('branch_id')->update(['branch_id' => $firstActiveBranchId]);
        }

        Schema::table('leave_types', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // Schema::getIndexes() (not a raw SHOW INDEX query) so this runs on
        // both MySQL (production) and SQLite (the in-memory test suite).
        $indexes = collect(Schema::getIndexes('leave_types'))->pluck('name');
        if ($indexes->contains('leave_types_name_unique')) {
            Schema::table('leave_types', fn (Blueprint $table) => $table->dropUnique('leave_types_name_unique'));
        }
        if ($indexes->contains('leave_types_code_unique')) {
            Schema::table('leave_types', fn (Blueprint $table) => $table->dropUnique('leave_types_code_unique'));
        }

        Schema::table('leave_types', function (Blueprint $table) {
            $table->unique(['branch_id', 'name']);
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'name']);
            $table->dropUnique(['branch_id', 'code']);
            $table->unique('name');
            $table->unique('code');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
