<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->decimal('salary_from', 12, 2)->nullable()->after('name');
            $table->decimal('salary_to', 12, 2)->nullable()->after('salary_from');
        });
    }

    public function down(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->dropColumn(['salary_from', 'salary_to']);
        });
    }
};
