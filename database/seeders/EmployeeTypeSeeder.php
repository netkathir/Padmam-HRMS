<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('employee_types')->insert([
            ['name' => 'Permanent',   'code' => 'PERM', 'description' => 'Full-time permanent employee'],
            ['name' => 'Contract',    'code' => 'CONT', 'description' => 'Fixed-term contract employee'],
            ['name' => 'Intern',      'code' => 'INTN', 'description' => 'Internship / trainee'],
            ['name' => 'Part-Time',   'code' => 'PART', 'description' => 'Part-time employee'],
            ['name' => 'Consultant',  'code' => 'CONS', 'description' => 'External consultant'],
            ['name' => 'Probationer', 'code' => 'PROB', 'description' => 'Employee on probation'],
        ]);
    }
}
