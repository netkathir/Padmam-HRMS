<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('settings')->insert([
            ['group' => 'general',    'key' => 'timezone',          'value' => 'Asia/Kolkata',  'type' => 'string',  'description' => 'Application timezone',          'is_public' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general',    'key' => 'date_format',       'value' => 'd-m-Y',         'type' => 'string',  'description' => 'Date display format',           'is_public' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general',    'key' => 'time_format',       'value' => 'H:i',           'type' => 'string',  'description' => 'Time display format',           'is_public' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general',    'key' => 'week_start_day',    'value' => '1',             'type' => 'integer', 'description' => '1=Monday 0=Sunday',             'is_public' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general',    'key' => 'currency',          'value' => 'INR',           'type' => 'string',  'description' => 'Default currency',              'is_public' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'general',    'key' => 'currency_symbol',   'value' => '₹',             'type' => 'string',  'description' => 'Currency symbol',               'is_public' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'payroll',    'key' => 'payroll_cycle',     'value' => 'monthly',       'type' => 'string',  'description' => 'Payroll processing cycle',      'is_public' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'payroll',    'key' => 'pay_day',           'value' => '28',            'type' => 'integer', 'description' => 'Day of month salary is paid',   'is_public' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'payroll',    'key' => 'lop_calc_method',   'value' => 'calendar_days', 'type' => 'string',  'description' => 'LOP deduction basis',           'is_public' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'attendance', 'key' => 'late_mark_policy',  'value' => 'deduct_half',   'type' => 'string',  'description' => 'Action for repeated late marks', 'is_public' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'attendance', 'key' => 'ot_eligible_after', 'value' => '0',             'type' => 'integer', 'description' => 'OT starts after N extra mins',  'is_public' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
