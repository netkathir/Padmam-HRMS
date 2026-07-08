<?php
// File: tests/Feature/ModulePermissionAccessTest.php
// Purpose: Verify module routes are gated by the logged-in user's role
//          permissions — denied with 403 when missing, allowed once granted,
//          and always allowed for super_admin.
// Author: System
// Date: 2026-07-08

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function actingUserWithRole(string $roleName): User
    {
        $role = Role::create([
            'name' => $roleName,
            'display_name' => ucfirst($roleName),
            'is_active' => true,
        ]);

        return User::create([
            'name' => 'Test User',
            'email' => "$roleName@example.com",
            'username' => $roleName,
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);
    }

    public function test_role_without_permission_gets_403(): void
    {
        $user = $this->actingUserWithRole('no_access_role');

        $response = $this->actingAs($user)->get('http://localhost/attendance');

        $response->assertStatus(403);
    }

    public function test_role_with_permission_gets_200(): void
    {
        $user = $this->actingUserWithRole('attendance_reader');

        Permission::syncModules();
        $readPermission = Permission::where('module', 'attendance')->where('access_level', 'read')->first();
        $user->role->permissions()->sync([$readPermission->id]);

        $response = $this->actingAs($user)->get('http://localhost/attendance');

        $response->assertStatus(200);
    }

    public function test_role_with_permission_for_one_module_is_denied_another(): void
    {
        $user = $this->actingUserWithRole('payroll_only');

        Permission::syncModules();
        $payrollRead = Permission::where('module', 'payroll')->where('access_level', 'read')->first();
        $user->role->permissions()->sync([$payrollRead->id]);

        $this->actingAs($user)->get('http://localhost/payroll')->assertStatus(200);
        $this->actingAs($user)->get('http://localhost/attendance')->assertStatus(403);
    }

    public function test_super_admin_bypasses_all_module_permission_checks(): void
    {
        $user = $this->actingUserWithRole('super_admin');

        $response = $this->actingAs($user)->get('http://localhost/attendance');

        $response->assertStatus(200);
    }

    public function test_masters_submodule_route_is_gated_by_its_own_permission(): void
    {
        $user = $this->actingUserWithRole('departments_only');

        Permission::syncModules();
        $deptRead = Permission::where('module', 'masters_departments')->where('access_level', 'read')->first();
        $user->role->permissions()->sync([$deptRead->id]);

        $this->actingAs($user)->get('http://localhost/masters/departments')->assertStatus(200);
        $this->actingAs($user)->get('http://localhost/masters/branches')->assertStatus(403);
    }

    public function test_admin_role_route_is_gated_by_its_own_permission(): void
    {
        $user = $this->actingUserWithRole('no_admin_access');

        $response = $this->actingAs($user)->get('http://localhost/admin/permissions');

        $response->assertStatus(403);
    }
}
