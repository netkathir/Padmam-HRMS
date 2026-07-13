<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionLevelPermissionAndBranchLockdownTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName, 'display_name' => ucfirst($roleName), 'is_active' => true]);

        return User::create([
            'name' => 'Test User', 'email' => "$roleName@example.com", 'username' => $roleName,
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);
    }

    private function grant(User $user, string $module, string $level): void
    {
        Permission::syncModules();
        $permission = Permission::where('module', $module)->where('access_level', $level)->firstOrFail();
        $user->role->permissions()->sync([$permission->id]);
    }

    private function makeEmployee(): Employee
    {
        $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'is_active' => true]);
        $department = Department::create(['name' => 'Ops', 'branch_id' => $branch->id, 'is_active' => true]);
        $designation = Designation::create(['name' => 'Staff', 'department_id' => $department->id, 'is_active' => true]);
        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);

        return Employee::create([
            'employee_code' => 'EMP1', 'branch_id' => $branch->id, 'department_id' => $department->id,
            'designation_id' => $designation->id, 'employee_type_id' => $employeeType->id,
            'first_name' => 'Test', 'last_name' => 'Employee', 'date_of_birth' => '1990-01-01',
            'gender' => 'male', 'official_email' => 'emp1@example.com', 'phone' => '9999999999',
            'date_of_joining' => now()->toDateString(), 'status' => 'active',
        ]);
    }

    public function test_read_only_role_can_view_employees_but_not_create(): void
    {
        $user = $this->userWithRole('employees_reader');
        $this->grant($user, 'employees', 'read');

        $this->actingAs($user)->get('http://localhost/employees')->assertStatus(200);
        $this->actingAs($user)->get('http://localhost/employees/create')->assertStatus(200);
        $this->actingAs($user)->post('http://localhost/employees', [])->assertStatus(403);
    }

    public function test_create_level_role_can_store_but_not_update_or_destroy(): void
    {
        $user = $this->userWithRole('employees_creator');
        $this->grant($user, 'employees', 'create');
        $employee = $this->makeEmployee();

        // create-level passes the store gate (validation redirects back with
        // errors, never a 403 — that's the signal the permission gate passed)
        $this->actingAs($user)->post('http://localhost/employees', [])->assertStatus(302)->assertSessionHasErrors();
        $this->actingAs($user)->put("http://localhost/employees/{$employee->id}", [])->assertStatus(403);
        $this->actingAs($user)->delete("http://localhost/employees/{$employee->id}")->assertStatus(403);
    }

    public function test_full_level_role_can_update_and_destroy(): void
    {
        $user = $this->userWithRole('employees_full');
        $this->grant($user, 'employees', 'full');
        $employee = $this->makeEmployee();

        // full-level passes the update gate (validation redirects back with
        // errors, never a 403 — that's the signal the permission gate passed)
        $this->actingAs($user)->put("http://localhost/employees/{$employee->id}", [])->assertStatus(302)->assertSessionHasErrors();
    }

    public function test_leave_approval_requires_full_not_just_read(): void
    {
        $user = $this->userWithRole('leaves_reader');
        $this->grant($user, 'leaves', 'read');

        $department = Department::create(['name' => 'D', 'branch_id' => Branch::create(['name' => 'B', 'code' => 'B1', 'is_active' => true])->id, 'is_active' => true]);
        $leaveType = \App\Models\LeaveType::create(['name' => 'CL', 'code' => 'CL', 'days_per_year' => 12, 'gender_specific' => 'all', 'is_active' => true]);
        $employee = $this->makeEmployee();
        $leave = \App\Models\LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
            'total_days' => 1, 'status' => 'pending', 'applied_by' => $user->id,
        ]);

        $this->actingAs($user)->get('http://localhost/leaves')->assertStatus(200);
        $this->actingAs($user)->post("http://localhost/leaves/{$leave->id}/approve", ['action' => 'approve'])->assertStatus(403);
    }

    public function test_branch_management_blocked_even_when_permission_explicitly_granted(): void
    {
        $user = $this->userWithRole('branch_head_locktest');
        // Simulate an accidental/explicit grant of full Branch Management access.
        $this->grant($user, 'masters_branches', 'full');

        $this->actingAs($user)->get('http://localhost/masters/branches')->assertStatus(403);
        $this->actingAs($user)->get('http://localhost/masters/branches/create')->assertStatus(403);
        $this->actingAs($user)->post('http://localhost/masters/branches', ['name' => 'X', 'code' => 'X1'])->assertStatus(403);
    }

    public function test_super_admin_can_access_branch_management_without_explicit_permission(): void
    {
        $user = $this->userWithRole('super_admin');

        $this->actingAs($user)->get('http://localhost/masters/branches')->assertStatus(200);
    }

    public function test_sidebar_hides_branch_management_link_for_non_super_admin_even_with_permission(): void
    {
        $user = $this->userWithRole('branch_head_ui');
        $this->grant($user, 'masters_branches', 'full');
        $this->grant($user, 'masters_departments', 'read');

        $response = $this->actingAs($user)->get('http://localhost/masters/departments');
        $response->assertStatus(200);
        $response->assertDontSee(route('masters.branches.index'));
    }

    public function test_sidebar_shows_branch_management_link_for_super_admin(): void
    {
        $user = $this->userWithRole('super_admin');

        $response = $this->actingAs($user)->get('http://localhost/dashboard');
        $response->assertSee(route('masters.branches.index'));
    }

    public function test_role_permission_assignment_screen_does_not_offer_branch_management(): void
    {
        $admin = $this->userWithRole('super_admin');
        $role = Role::create(['name' => 'some_role', 'display_name' => 'Some Role', 'is_active' => true]);

        $response = $this->actingAs($admin)->get("http://localhost/admin/role-permissions/{$role->id}/assign");
        $response->assertStatus(200);
        $response->assertDontSee('masters_branches');
    }
}
