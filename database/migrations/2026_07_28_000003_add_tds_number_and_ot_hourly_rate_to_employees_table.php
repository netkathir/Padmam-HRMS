<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('tds_number', 30)->nullable()->after('esi_number');
            $table->decimal('ot_hourly_rate', 8, 2)->nullable()->after('is_ot_applicable');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['tds_number', 'ot_hourly_rate']);
        });
    }
};
