<?php
// File: database/migrations/2026_07_21_000002_add_upload_link_to_attendance_logs_table.php
// Purpose: Module 7 FSD 11.2 — links a raw punch row back to the biometric
//          upload batch that produced it.
// Author: System
// Date: 2026-07-21

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_logs', 'biometric_upload_id')) {
                $table->unsignedInteger('biometric_upload_id')->nullable()->after('employee_code');
                $table->index('biometric_upload_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'biometric_upload_id')) {
                $table->dropColumn('biometric_upload_id');
            }
        });
    }
};
