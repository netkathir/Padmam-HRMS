<?php
// Purpose: designations.name/code had NO uniqueness enforced anywhere
// (app validation or DB). Scoped by department_id — not a separate
// branch_id — since Designation is already visibility-scoped through its
// (per-branch) Department, and it may optionally have no department at
// all (a global designation). Two departments, whether in the same branch
// or different branches, may legitimately share a designation name/code.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->unique(['department_id', 'name']);
            $table->unique(['department_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropUnique(['department_id', 'name']);
            $table->dropUnique(['department_id', 'code']);
        });
    }
};
