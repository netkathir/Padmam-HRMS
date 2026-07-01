<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('leave_types')->insert([
            ['name' => 'Earned Leave',     'code' => 'EL',  'days_per_year' => 15, 'max_carry_forward' => 30, 'is_carry_forward' => 1, 'is_paid' => 1, 'is_half_day_allowed' => 1, 'gender_specific' => 'all'],
            ['name' => 'Casual Leave',     'code' => 'CL',  'days_per_year' => 8,  'max_carry_forward' => 0,  'is_carry_forward' => 0, 'is_paid' => 1, 'is_half_day_allowed' => 1, 'gender_specific' => 'all'],
            ['name' => 'Sick Leave',       'code' => 'SL',  'days_per_year' => 7,  'max_carry_forward' => 0,  'is_carry_forward' => 0, 'is_paid' => 1, 'is_half_day_allowed' => 1, 'gender_specific' => 'all'],
            ['name' => 'Loss of Pay',      'code' => 'LOP', 'days_per_year' => 0,  'max_carry_forward' => 0,  'is_carry_forward' => 0, 'is_paid' => 0, 'is_half_day_allowed' => 1, 'gender_specific' => 'all'],
            ['name' => 'Maternity Leave',  'code' => 'ML',  'days_per_year' => 26, 'max_carry_forward' => 0,  'is_carry_forward' => 0, 'is_paid' => 1, 'is_half_day_allowed' => 0, 'gender_specific' => 'female'],
            ['name' => 'Paternity Leave',  'code' => 'PL',  'days_per_year' => 5,  'max_carry_forward' => 0,  'is_carry_forward' => 0, 'is_paid' => 1, 'is_half_day_allowed' => 0, 'gender_specific' => 'male'],
            ['name' => 'Compensatory Off', 'code' => 'CO',  'days_per_year' => 0,  'max_carry_forward' => 0,  'is_carry_forward' => 0, 'is_paid' => 1, 'is_half_day_allowed' => 1, 'gender_specific' => 'all'],
            ['name' => 'Optional Holiday', 'code' => 'OH',  'days_per_year' => 2,  'max_carry_forward' => 0,  'is_carry_forward' => 0, 'is_paid' => 1, 'is_half_day_allowed' => 0, 'gender_specific' => 'all'],
        ]);
    }
}
