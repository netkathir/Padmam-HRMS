<?php
// Purpose: Checkpoint Master — each checkpoint (SPP, SPI, SGI, etc.) belongs
// to a specific Branch, same pattern as Department/Designation. A
// branch-scoped user only manages/sees their own branch's checkpoints;
// Super Admin sees all (or whichever branch is currently switched to, for
// data entry — same convention as every other branch-scoped master).
// Table affected: checkpoints (adds branch_id).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            // branches.id is smallIncrements() (unsignedSmallInteger) —
            // matched exactly, since MySQL 8 requires identical column
            // types for a foreign key (see the employees.id lesson from the
            // employee_checkpoints migration).
            $table->unsignedSmallInteger('branch_id')->nullable()->after('id');
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });

        // name/code were globally unique when Checkpoint had no branch
        // dimension — now that each branch has its own checkpoints, two
        // different branches must be able to each have their own "SPP".
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropUnique('checkpoints_name_unique');
            $table->dropUnique('checkpoints_code_unique');
            $table->unique(['branch_id', 'name'], 'checkpoints_branch_name_unique');
            $table->unique(['branch_id', 'code'], 'checkpoints_branch_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropUnique('checkpoints_branch_name_unique');
            $table->dropUnique('checkpoints_branch_code_unique');
            $table->unique('name', 'checkpoints_name_unique');
            $table->unique('code', 'checkpoints_code_unique');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
