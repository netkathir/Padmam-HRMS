<?php
// File: database/seeders/SuperAdminSeeder.php
// Purpose: Seed module-level permissions (4 access levels each), assign all to super_admin, upsert Super Admin user
// Author: System
// Date: 2026-06-30

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Ensure super_admin role exists ─────────────────────────────
        $role = DB::table('roles')->where('name', 'super_admin')->first();

        if (! $role) {
            $roleId = DB::table('roles')->insertGetId([
                'name'         => 'super_admin',
                'display_name' => 'Super Administrator',
                'description'  => 'Full system access — no restrictions',
                'is_active'    => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $this->command->info('  Created super_admin role.');
        } else {
            $roleId = $role->id;
            $this->command->info("  super_admin role exists (id={$roleId}).");
        }

        // ── 2. Define all permissions (one row per module in config/menu_modules.php
        //         × access level). Access-level hierarchy (highest grants all lower
        //         within the same module): delete(4) > full(3) > create(2) > read(1)
        Permission::syncModules();

        $totalPermissions = DB::table('permissions')->count();
        $this->command->info("  Permissions in table: {$totalPermissions}");

        // ── 3. Assign ALL permissions to super_admin role ─────────────────
        $allPermissionIds = DB::table('permissions')->pluck('id');

        DB::table('role_permissions')->where('role_id', $roleId)->delete();

        $rolePermissions = $allPermissionIds->map(fn($pid) => [
            'role_id'       => $roleId,
            'permission_id' => $pid,
        ])->all();

        DB::table('role_permissions')->insert($rolePermissions);
        $this->command->info("  Assigned {$allPermissionIds->count()} permissions to super_admin role.");

        // ── 4. Upsert the Super Admin user ────────────────────────────────
        $existingUser = DB::table('users')
            ->where(function ($q) use ($roleId) {
                $q->where('email',    'superadmin@hrms.local')
                  ->orWhere('username', 'superadmin')
                  ->orWhere(function ($q2) use ($roleId) {
                      $q2->where('role_id', $roleId)->whereNull('deleted_at');
                  });
            })
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        $userData = [
            'name'       => 'Super Admin',
            'email'      => 'superadmin@hrms.local',
            'username'   => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'role_id'    => $roleId,
            'is_active'  => 1,
            'updated_at' => now(),
        ];

        if ($existingUser) {
            DB::table('users')->where('id', $existingUser->id)->update($userData);
            $this->command->info("  Updated existing user (id={$existingUser->id}) → superadmin@hrms.local");
        } else {
            $userData['created_at'] = now();
            $newId = DB::table('users')->insertGetId($userData);
            $this->command->info("  Created new Super Admin user (id={$newId}).");
        }

        $this->command->info('');
        $this->command->info('  ✔  Super Admin setup complete.');
        $this->command->info('  Email   : superadmin@hrms.local');
        $this->command->info('  Username: superadmin');
        $this->command->info('  Password: Admin@123');
    }
}
