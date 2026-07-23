<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earnings_components', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
    }

    public function down(): void
    {
        Schema::table('earnings_components', function (Blueprint $table) {
            $table->decimal('percentage', 5, 2)->nullable();
        });
    }
};
