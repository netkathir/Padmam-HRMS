<?php
// File: database/migrations/2026_07_21_000001_add_biometric_fields_to_attendance_table.php
// Purpose: Module 7 FSD 11.3/11.4/11.5 — extends `attendance` for biometric
//          processing and correction:
//          - `status` gains 8 FSD values (paid_leave, unpaid_leave,
//            weekly_off, paid_holiday, unpaid_holiday, on_duty,
//            missing_punch, pending_review). Existing values (holiday,
//            weekend, on_leave, late, early_exit) are KEPT, not removed, so
//            existing rows keep displaying correctly — new code prefers the
//            richer set going forward.
//          - `source` gains `corrected` (existing: web/mobile/biometric/manual).
//          - `leave_type_id`, `lop_days` — FSD 11.4 register display fields.
//          - `correction_reason` — dedicated column (previously the reason
//            overwrote the general-purpose `remarks` field — see
//            AttendanceController).
//          - `supporting_document_path` — FSD 11.5 correction upload.
//          - `ot_approval_status`/`ot_approved_by`/`ot_approved_at` — a
//            SEPARATE approval concept from the existing
//            `approval_status`/`approved_by`/`approved_at` (which remains
//            exactly as-is for manual-entry regularisation); overtime
//            approval is distinct per FSD 11.4's "Approve Overtime" action.
//          - `biometric_upload_id` — traces a day back to the upload batch
//            that produced its punches, when applicable.
// Author: System
// Date: 2026-07-21

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM alteration has no clean Schema-builder helper in Laravel —
        // done via raw DDL, guarded so re-running is a no-op.
        $statusColumn = DB::selectOne("SHOW COLUMNS FROM `attendance` LIKE 'status'");
        if ($statusColumn && ! str_contains($statusColumn->Type, 'missing_punch')) {
            DB::statement("ALTER TABLE `attendance` MODIFY `status` ENUM(
                'present','absent','half_day','holiday','weekend','on_leave','late','early_exit',
                'paid_leave','unpaid_leave','weekly_off','paid_holiday','unpaid_holiday','on_duty','missing_punch','pending_review'
            ) NOT NULL DEFAULT 'absent'");
        }

        $sourceColumn = DB::selectOne("SHOW COLUMNS FROM `attendance` LIKE 'source'");
        if ($sourceColumn && ! str_contains($sourceColumn->Type, 'corrected')) {
            DB::statement("ALTER TABLE `attendance` MODIFY `source` ENUM('web','mobile','biometric','manual','corrected') NOT NULL DEFAULT 'manual'");
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance', 'leave_type_id')) {
                $table->unsignedTinyInteger('leave_type_id')->nullable()->after('status');
                $table->foreign('leave_type_id')->references('id')->on('leave_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('attendance', 'lop_days')) {
                $table->decimal('lop_days', 4, 2)->nullable()->after('leave_type_id');
            }
            if (! Schema::hasColumn('attendance', 'correction_reason')) {
                $table->text('correction_reason')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('attendance', 'supporting_document_path')) {
                $table->string('supporting_document_path', 255)->nullable()->after('correction_reason');
            }
            if (! Schema::hasColumn('attendance', 'ot_approval_status')) {
                $table->enum('ot_approval_status', ['pending', 'approved', 'rejected'])->nullable()->after('supporting_document_path');
            }
            if (! Schema::hasColumn('attendance', 'ot_approved_by')) {
                $table->unsignedInteger('ot_approved_by')->nullable()->after('ot_approval_status');
                $table->foreign('ot_approved_by')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('attendance', 'ot_approved_at')) {
                $table->dateTime('ot_approved_at')->nullable()->after('ot_approved_by');
            }
            if (! Schema::hasColumn('attendance', 'biometric_upload_id')) {
                $table->unsignedInteger('biometric_upload_id')->nullable()->after('ot_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'ot_approved_by')) {
                $table->dropForeign(['ot_approved_by']);
            }
            if (Schema::hasColumn('attendance', 'leave_type_id')) {
                $table->dropForeign(['leave_type_id']);
            }
            foreach ([
                'biometric_upload_id', 'ot_approved_at', 'ot_approved_by', 'ot_approval_status',
                'supporting_document_path', 'correction_reason', 'lop_days', 'leave_type_id',
            ] as $col) {
                if (Schema::hasColumn('attendance', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
