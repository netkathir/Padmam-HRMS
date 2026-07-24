<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->decimal('lop_percentage', 5, 2)->default(100)->after('esi_employer_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('salary_slabs', function (Blueprint $table) {
            $table->dropColumn('lop_percentage');
        });
    }
};
