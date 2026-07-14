<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch Create screen rework — splits address entry into Address Line 1/2,
 * matching the existing employees.address_line1/address_line2 convention.
 * The legacy `address` column is kept as-is (still the field the Edit
 * Branch screen and validation operate on); these are additive columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('address_line1', 200)->nullable()->after('address');
            $table->string('address_line2', 200)->nullable()->after('address_line1');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['address_line1', 'address_line2']);
        });
    }
};
