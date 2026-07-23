<?php
// Purpose: biometric_number is a plain 3-digit employee number with no
// branch prefix (the branch is identified separately, from the biometric
// upload's own Person ID prefix against Branch.code) — so two employees in
// DIFFERENT branches may legitimately share the same 3-digit number. Drops
// the single-column global unique index added in
// 2026_07_29_000001_add_biometric_number_to_employees_table.php and
// replaces it with a composite (branch_id, biometric_number) unique index.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['biometric_number']);
            $table->unique(['branch_id', 'biometric_number']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'biometric_number']);
            $table->unique('biometric_number');
        });
    }
};
