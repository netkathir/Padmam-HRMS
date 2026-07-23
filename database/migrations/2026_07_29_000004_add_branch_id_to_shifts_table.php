<?php
// Purpose: record which single branch a Shift was created under (auto-
// stamped from the currently active branch, same pattern as Department/
// Designation) — NOT the previously-removed multi-branch "Branch
// Applicability" feature (shift_branches pivot), which stays removed.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // branches.id is unsignedSmallInteger (smallIncrements), not the
            // bigInteger foreignId() assumes — must match exactly or MySQL
            // rejects the foreign key with error 3780.
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->index('branch_id');
        });

        // Existing shifts predate this column — attribute them to the
        // first active branch so historical rows aren't left branchless
        // (branch-scoped list queries would otherwise silently hide them).
        $firstBranchId = DB::table('branches')->where('is_active', true)->orderBy('id')->value('id');
        if ($firstBranchId) {
            DB::table('shifts')->whereNull('branch_id')->update(['branch_id' => $firstBranchId]);
        }
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
