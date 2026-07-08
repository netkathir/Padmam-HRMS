<?php
// File: database/seeders/PermissionSeeder.php
// Purpose: Seed one permission row per module (config/menu_modules.php) x access level
// Author: System
// Date: 2026-07-08

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::syncModules();

        $this->command->info('  Permissions in table: ' . Permission::count());
    }
}
