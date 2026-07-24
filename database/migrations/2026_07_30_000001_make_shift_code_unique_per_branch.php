<?php
// Purpose: shifts.code restarts numbering from 0001 in EACH branch (e.g.
// branch A's 5th shift and branch B's 1st shift can both be "SH0001") —
// the old global unique index on `code` alone would reject that, exactly
// like employee_code before 2026_07_24_000001_make_employee_code_unique_per_branch.php.
// Uniqueness moves to the (branch_id, code) pair.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique('shifts_code_unique');
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'code']);
            $table->unique('code');
        });
    }
};
