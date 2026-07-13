<?php
// File: database/migrations/2026_07_14_000007_seed_holiday_sunday_pay_settings.php
// Purpose: Module 3 FSD 7.3 — "Sunday shall be treated as a paid weekly holiday
//          for Staff by default. Sunday treatment for Company Labour and
//          Contract Labour shall be configurable." Staff stays a fixed `true`
//          (not configurable, so not stored); these two rows back the
//          configurable toggle for the other two employee types, reusing the
//          existing generic `settings` table (group/key/value) rather than a
//          new table.
// Author: System
// Date: 2026-07-14

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'holiday',
                'key' => 'sunday_paid_company_labour',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Sunday Paid - Company Labour: whether Sunday is treated as a paid weekly holiday for Company Labour employees.',
            ],
            [
                'group' => 'holiday',
                'key' => 'sunday_paid_contract_labour',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Sunday Paid - Contract Labour: whether Sunday is treated as a paid weekly holiday for Contract Labour employees.',
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'holiday')
            ->whereIn('key', ['sunday_paid_company_labour', 'sunday_paid_contract_labour'])
            ->delete();
    }
};
