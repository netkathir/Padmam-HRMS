<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'Full system access'],
            ['name' => 'admin',       'display_name' => 'Administrator',       'description' => 'Company-wide admin'],
            ['name' => 'hr',          'display_name' => 'HR Manager',          'description' => 'Human resources operations'],
            ['name' => 'manager',     'display_name' => 'Manager',             'description' => 'Team / department manager'],
            ['name' => 'employee',    'display_name' => 'Employee',            'description' => 'Standard employee access'],
        ]);
    }
}
