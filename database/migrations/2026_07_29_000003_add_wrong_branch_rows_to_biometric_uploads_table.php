<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biometric_uploads', function (Blueprint $table) {
            $table->unsignedInteger('wrong_branch_rows')->default(0)->after('unknown_employee_rows');
        });
    }

    public function down(): void
    {
        Schema::table('biometric_uploads', function (Blueprint $table) {
            $table->dropColumn('wrong_branch_rows');
        });
    }
};
