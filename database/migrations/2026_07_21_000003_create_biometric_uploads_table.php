<?php
// File: database/migrations/2026_07_21_000003_create_biometric_uploads_table.php
// Purpose: Module 7 FSD 11.2 — biometric Excel upload batch header: Branch,
//          Attendance Period From/To, the stored file + detected sheet,
//          the confirmed column mapping, Upload Remarks, Uploaded By/At,
//          and the FSD-mandated validation summary (6 counts) + a
//          downloadable error file path.
// Author: System
// Date: 2026-07-21

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('biometric_uploads')) {
            Schema::create('biometric_uploads', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedSmallInteger('branch_id');
                $table->date('period_from');
                $table->date('period_to');
                $table->string('file_path', 255);
                $table->string('original_filename', 255);
                $table->string('sheet_name', 100)->nullable();
                $table->json('column_mapping')->nullable();
                $table->string('remarks', 255)->nullable();
                $table->unsignedInteger('uploaded_by');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_rows')->default(0);
                $table->unsignedInteger('invalid_rows')->default(0);
                $table->unsignedInteger('duplicate_rows')->default(0);
                $table->unsignedInteger('unknown_employee_rows')->default(0);
                $table->unsignedInteger('invalid_date_rows')->default(0);
                $table->unsignedInteger('invalid_time_rows')->default(0);
                $table->string('error_file_path', 255)->nullable();
                $table->enum('status', ['mapping', 'processing', 'completed', 'failed'])->default('mapping');
                $table->timestamps();

                $table->index(['branch_id', 'period_from', 'period_to']);

                $table->foreign('branch_id')->references('id')->on('branches');
                $table->foreign('uploaded_by')->references('id')->on('users');
            });
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (! $this->hasForeignKey('attendance', 'attendance_biometric_upload_id_foreign')) {
                $table->foreign('biometric_upload_id')->references('id')->on('biometric_uploads')->nullOnDelete();
            }
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            if (! $this->hasForeignKey('attendance_logs', 'attendance_logs_biometric_upload_id_foreign')) {
                $table->foreign('biometric_upload_id')->references('id')->on('biometric_uploads')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if ($this->hasForeignKey('attendance_logs', 'attendance_logs_biometric_upload_id_foreign')) {
                $table->dropForeign(['biometric_upload_id']);
            }
        });
        Schema::table('attendance', function (Blueprint $table) {
            if ($this->hasForeignKey('attendance', 'attendance_biometric_upload_id_foreign')) {
                $table->dropForeign(['biometric_upload_id']);
            }
        });
        Schema::dropIfExists('biometric_uploads');
    }

    private function hasForeignKey(string $table, string $fkName): bool
    {
        $result = \Illuminate\Support\Facades\DB::selectOne(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $fkName]
        );
        return (bool) $result;
    }
};
