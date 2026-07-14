<?php
// File: database/migrations/2026_07_22_000002_seed_payroll_module_settings.php
// Purpose: Module 9 — configurable Payroll Management settings, reusing the
//          existing generic `settings` table exactly like every prior
//          module's config flags: whether a negative net salary blocks
//          generation or only flags it (FSD 13.6), whether employer PF/ESI
//          shows on the payslip (FSD 13.7 "when configured for display"),
//          whether payslip generation requires payroll confirmation/closure
//          (FSD 13.7), and whether payslip email is enabled (FSD 13.7 "when
//          enabled").
// Author: System
// Date: 2026-07-22

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'payroll',
                'key' => 'block_negative_net_salary',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'FSD 13.6 - when true, an employee whose net salary would be negative is excluded from generation and listed as an exception instead of being saved with a negative value; when false, the record is saved with a visible warning flag.',
            ],
            [
                'group' => 'payroll',
                'key' => 'show_employer_contribution_on_payslip',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'FSD 13.7 - whether Employer PF and Employer ESI contributions are shown on the payslip.',
            ],
            [
                'group' => 'payroll',
                'key' => 'payslip_requires_confirmation',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'FSD 13.7 - payslip shall be generated only after payroll confirmation or closure, according to configuration.',
            ],
            [
                'group' => 'payroll',
                'key' => 'payslip_email_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'FSD 13.7 - Email payslip action, when enabled.',
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'payroll')
            ->whereIn('key', [
                'block_negative_net_salary', 'show_employer_contribution_on_payslip',
                'payslip_requires_confirmation', 'payslip_email_enabled',
            ])
            ->delete();
    }
};
