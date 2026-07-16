<?php
// Purpose: Employee Master — Designation & Salary tab gets a new, manually
// user-toggled "OT Applicable" checkbox, mirroring the existing
// is_pf_applicable/is_esi_applicable/is_tds_applicable sibling flags.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'is_ot_applicable')) {
                $table->boolean('is_ot_applicable')->default(false)->after('is_tds_applicable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'is_ot_applicable')) {
                $table->dropColumn('is_ot_applicable');
            }
        });
    }
};
