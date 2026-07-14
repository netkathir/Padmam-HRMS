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
            ['name' => 'Earned Leave',     'code' => 'EL',  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Casual Leave',     'code' => 'CL',  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Sick Leave',       'code' => 'SL',  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Loss of Pay',      'code' => 'LOP', 'is_paid' => 0, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Maternity Leave',  'code' => 'ML',  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Paternity Leave',  'code' => 'PL',  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Compensatory Off', 'code' => 'CO',  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
            ['name' => 'Optional Holiday', 'code' => 'OH',  'is_paid' => 1, 'applicable_employee_types' => $allTypes, 'is_active' => 1],
        ]);
    }
}
