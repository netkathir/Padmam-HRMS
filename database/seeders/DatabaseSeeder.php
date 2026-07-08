<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            EmployeeTypeSeeder::class,
            LeaveTypeSeeder::class,
            EarningsComponentSeeder::class,
            DeductionsComponentSeeder::class,
            PfEsiConfigSeeder::class,
            ShiftSeeder::class,
            CompanyProfileSeeder::class,
            SettingsSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
