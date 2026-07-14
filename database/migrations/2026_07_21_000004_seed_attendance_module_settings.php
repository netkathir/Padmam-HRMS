<?php
// File: database/migrations/2026_07_21_000004_seed_attendance_module_settings.php
// Purpose: Module 7 FSD 11.2 — "configurable column mapping" default,
//          reusing the generic `settings` table exactly like Modules 4/6/8's
//          Settings-driven config. This is a best-guess default the mapping
//          screen pre-fills from (matching detected Excel header text
//          case-insensitively); the user can still override per-upload.
// Author: System
// Date: 2026-07-21

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'attendance',
                'key' => 'default_excel_column_mapping',
                'value' => json_encode([
                    'employee_number' => 'Employee Number',
                    'biometric_id'    => 'Biometric ID',
                    'employee_name'   => 'Employee Name',
                    'punch_date'      => 'Punch Date',
                    'punch_time'      => 'Punch Time',
                    'punch_type'      => 'Punch Type',
                    'device_id'       => 'Device ID',
                    'location'        => 'Location',
                    'shift_code'      => 'Shift Code',
                ]),
                'type' => 'json',
                'description' => 'Best-guess default column mapping (by header text) pre-filled on the biometric upload mapping screen; overridable per upload.',
            ],
            [
                'group' => 'attendance',
                'key' => 'allow_manual_when_no_biometric',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'FSD 11.3 — "Attendance shall not be processed without valid biometric data unless manual attendance entry is permitted." When true, the existing manual entry/mark screens remain usable regardless of biometric upload status.',
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'attendance')
            ->whereIn('key', ['default_excel_column_mapping', 'allow_manual_when_no_biometric'])
            ->delete();
    }
};
