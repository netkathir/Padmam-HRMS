<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $allTypes = json_encode(['staff', 'company_labour', 'contract_labour']);

        DB::table('leave_types')->insert([
            ['name' => 'Earned Leave',     'code' => 'EL',  'days_per_year' => 15, 'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Casual Leave',     'code' => 'CL',  'days_per_year' => 12, 'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Sick Leave',       'code' => 'SL',  'days_per_year' => 12, 'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Loss of Pay',      'code' => 'LOP', 'days_per_year' => 0,  'is_paid' => 0, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Maternity Leave',  'code' => 'ML',  'days_per_year' => 182, 'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Paternity Leave',  'code' => 'PL',  'days_per_year' => 5,  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Compensatory Off', 'code' => 'CO',  'days_per_year' => 0,  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Optional Holiday', 'code' => 'OH',  'days_per_year' => 2,  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
        ]);
    }
}
