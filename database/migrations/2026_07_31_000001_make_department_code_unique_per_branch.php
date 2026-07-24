<?php
// Purpose: departments.code was validated as per-branch at the app layer
// (DepartmentController) but the DB constraint was still a single-column
// global unique index — two branches legitimately reusing the same code
// would pass validation and then crash with a raw duplicate-key
// QueryException. Moves uniqueness to the (branch_id, code) pair, matching
// how departments.name already works.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_code_unique');
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'code']);
            $table->unique('code');
        });
    }
};
