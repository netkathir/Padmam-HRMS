<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $roleId = DB::table('roles')->where('name', 'super_admin')->value('id');

        DB::table('users')->insert([
            'name'       => 'Super Admin',
            'email'      => 'admin@hrms.com',
            'username'   => 'superadmin',
            'password'   => Hash::make('Admin@123'),
            'role_id'    => $roleId,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
