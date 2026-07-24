<?php
// Purpose: banks.code was globally unique at the DB level with no branch
// scoping. Per the system-wide branch-level uniqueness requirement, adds
// branch_id so two different branches may register a bank under the same
// code (name stays free-text/unscoped, matching how it was never unique
// before this change either).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
        });

        $firstActiveBranchId = DB::table('branches')->where('is_active', true)->orderBy('id')->value('id');
        if ($firstActiveBranchId) {
            DB::table('banks')->whereNull('branch_id')->update(['branch_id' => $firstActiveBranchId]);
        }

        Schema::table('banks', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // Schema::getIndexes() (not a raw SHOW INDEX query) so this runs on
        // both MySQL (production) and SQLite (the in-memory test suite).
        $indexes = collect(Schema::getIndexes('banks'))->pluck('name');
        if ($indexes->contains('banks_code_unique')) {
            Schema::table('banks', fn (Blueprint $table) => $table->dropUnique('banks_code_unique'));
        }

        Schema::table('banks', function (Blueprint $table) {
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'code']);
            $table->unique('code');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
