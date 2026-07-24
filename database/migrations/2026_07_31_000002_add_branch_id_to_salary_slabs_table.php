<?php
// Purpose: salary_slabs.name had no uniqueness validation at all, and the
// branch_id column that used to scope it was dropped by
// 2026_07_24_000003_simplify_salary_slabs_table_to_revised_fsd. Re-adding it
// so a Salary Slab name can be enforced unique per branch (same pattern as
// departments/shifts), rather than left completely unenforced.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
        });

        $firstActiveBranchId = DB::table('branches')->where('is_active', true)->orderBy('id')->value('id');
        if ($firstActiveBranchId) {
            DB::table('salary_slabs')->whereNull('branch_id')->update(['branch_id' => $firstActiveBranchId]);
        }

        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->unique(['branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'name']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
