<?php
// File: database/migrations/2026_07_17_000005_seed_employee_module_settings.php
// Purpose: Module 6 — configurable Employee Management settings, reusing
//          the existing generic `settings` table exactly like Module 4's
//          Sunday-pay settings: `employee.biometric_id_scope` (global/branch
//          uniqueness scope for Biometric ID), `employee.min_working_age`
//          (DOB minimum-age validation), `employee.mandatory_document_types`
//          (JSON list surfaced as a non-blocking warning banner).
// Author: System
// Date: 2026-07-17

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'employee',
                'key' => 'biometric_id_scope',
                'value' => 'global',
                'type' => 'string',
                'description' => 'Biometric ID uniqueness scope: global (unique across the whole company) or branch (unique within a branch).',
            ],
            [
                'group' => 'employee',
                'key' => 'min_working_age',
                'value' => '18',
                'type' => 'integer',
                'description' => 'Minimum working age enforced against Date of Birth at employee registration.',
            ],
            [
                'group' => 'employee',
                'key' => 'mandatory_document_types',
                'value' => '["aadhaar","photo"]',
                'type' => 'json',
                'description' => 'Document types that should be present before employee registration is considered complete (non-blocking warning).',
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'employee')
            ->whereIn('key', ['biometric_id_scope', 'min_working_age', 'mandatory_document_types'])
            ->delete();
    }
};
