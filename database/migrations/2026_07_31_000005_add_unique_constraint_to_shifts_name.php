<?php
// Purpose: Shift.name was already validated as per-branch-unique at the app
// layer (ShiftController::rules()) but the DB itself had no unique index on
// name at all — two near-simultaneous submissions could both pass validation
// and insert duplicate shift names in the same branch. Adds the missing
// (branch_id, name) composite unique index, matching shifts_branch_id_code_unique.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->unique(['branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'name']);
        });
    }
};
