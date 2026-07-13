<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\PayrollRecord;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function makeBranch(array $overrides = []): Branch
    {
        static $seq = 0;
        $seq++;

        return Branch::create(array_merge([
            'name' => "Branch $seq",
            'code' => "BR$seq",
            'is_active' => true,
        ], $overrides));
    }

    private function makeRole(string $name, array $overrides = []): Role
    {
        if ($existing = Role::where('name', $name)->first()) {
            $existing->update($overrides);
            return $existing;
        }

        return Role::create(array_merge([
            'name' => $name,
            'display_name' => ucfirst($name),
            'is_active' => true,
        ], $overrides));
    }

    private function superAdmin(): User
    {
        $role = $this->makeRole('super_admin');

        return User::create([
            'name' => 'Super Admin', 'email' => 'super@example.com', 'username' => 'superadmin',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);
    }

    private function makeEmployee(Branch $branch, array $overrides = []): Employee
    {
        static $seq = 0;
        $seq++;

        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);
        $department = \App\Models\Department::create(['name' => "Dept $seq", 'branch_id' => $branch->id, 'is_active' => true]);
        $designation = \App\Models\Designation::create(['name' => "Desig $seq", 'department_id' => $department->id, 'is_active' => true]);

        return Employee::create(array_merge([
            'employee_code' => "EMP$seq",
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employee_type_id' => $employeeType->id,
            'first_name' => 'Test', 'last_name' => "Employee$seq",
            'date_of_birth' => '1990-01-01', 'gender' => 'male',
            'official_email' => "emp$seq@example.com", 'phone' => '9999999999',
            'date_of_joining' => now()->toDateString(), 'status' => 'active',
        ], $overrides));
    }

    public function test_overall_dashboard_requires_permission(): void
    {
        $role = $this->makeRole('no_dashboard_access');
        $user = User::create([
            'name' => 'No Access', 'email' => 'noaccess@example.com', 'username' => 'noaccess',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);

        $this->actingAs($user)->get('http://localhost/dashboard')->assertStatus(403);
    }

    public function test_overall_dashboard_accessible_with_permission(): void
    {
        $role = $this->makeRole('has_dashboard_access');
        Permission::syncModules();
        $perm = Permission::where('module', 'dashboard')->where('access_level', 'read')->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$perm->id]);

        $user = User::create([
            'name' => 'Has Access', 'email' => 'hasaccess@example.com', 'username' => 'hasaccess',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);

        $this->actingAs($user)->get('http://localhost/dashboard')->assertStatus(200);
    }

    public function test_total_employees_kpi_excludes_inactive_employees(): void
    {
        $branch = $this->makeBranch();
        $this->makeEmployee($branch, ['status' => 'active']);
        $this->makeEmployee($branch, ['status' => 'inactive', 'official_email' => 'inactive@example.com', 'employee_code' => 'EMPX']);

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get('http://localhost/dashboard');
        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('kpis')['total_employees']);
    }

    public function test_branch_multiselect_ignores_unauthorized_branch_ids(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $this->makeEmployee($branchA);
        $this->makeEmployee($branchB, ['official_email' => 'b@example.com', 'employee_code' => 'EMPB']);

        // A branch-scoped Branch Head is only ever authorized for their own
        // branch (branchA) — submitting branchB's id must be silently
        // dropped, never widen their view.
        $headRole = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);
        Permission::syncModules();
        $dashPerm = Permission::where('module', 'dashboard')->where('access_level', 'read')->firstOrFail();
        $headRole->permissions()->syncWithoutDetaching([$dashPerm->id]);

        $head = User::create([
            'name' => 'Branch Head', 'email' => 'head@example.com', 'username' => 'branchhead',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $headRole->id,
            'user_type' => 'branch_head', 'branch_id' => $branchA->id,
        ]);

        $response = $this->actingAs($head)->get("http://localhost/dashboard?branch_ids[]={$branchB->id}");
        $response->assertStatus(200);

        // Total employees KPI must reflect only branchA (1), never branchB's.
        $this->assertEquals(1, $response->viewData('kpis')['total_employees']);
    }

    public function test_lop_employees_kpi_only_counts_processed_payroll(): void
    {
        $branch = $this->makeBranch();
        $employee = $this->makeEmployee($branch);

        PayrollRecord::create([
            'employee_id' => $employee->id,
            'month' => now()->month, 'year' => now()->year,
            'working_days' => 30, 'present_days' => 25, 'absent_days' => 0, 'leave_days' => 0,
            'lop_days' => 5, 'holiday_days' => 0, 'ot_hours' => 0,
            'basic_salary' => 10000, 'gross_earnings' => 10000, 'total_deductions' => 1000,
            'pf_employer' => 500, 'esi_employer' => 100, 'net_salary' => 9000,
            'status' => 'processed', 'generated_at' => now(),
        ]);

        $admin = $this->superAdmin();
        $response = $this->actingAs($admin)->get('http://localhost/dashboard');

        $kpis = $response->viewData('kpis');
        $this->assertEquals(1, $kpis['lop_employees']);
        $this->assertEquals(500.0, $kpis['employer_pf']);
        $this->assertEquals(100.0, $kpis['employer_esi']);
    }

    public function test_branch_dashboard_auto_selects_single_authorized_branch(): void
    {
        $branch = $this->makeBranch();
        $this->makeEmployee($branch);

        $headRole = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);
        Permission::syncModules();
        $perm = Permission::where('module', 'branch_dashboard')->where('access_level', 'read')->firstOrFail();
        $headRole->permissions()->syncWithoutDetaching([$perm->id]);

        $head = User::create([
            'name' => 'Branch Head', 'email' => 'head2@example.com', 'username' => 'branchhead2',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $headRole->id,
            'user_type' => 'branch_head', 'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($head)->get('http://localhost/dashboard/branch');
        $response->assertStatus(200);
        $this->assertEquals($branch->id, $response->viewData('filters')['branch_id']);
        $this->assertEquals(1, $response->viewData('kpis')['active_employees']);
    }

    public function test_branch_dashboard_rejects_unauthorized_branch_id(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();

        $headRole = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);
        Permission::syncModules();
        $perm = Permission::where('module', 'branch_dashboard')->where('access_level', 'read')->firstOrFail();
        $headRole->permissions()->syncWithoutDetaching([$perm->id]);

        $head = User::create([
            'name' => 'Branch Head', 'email' => 'head3@example.com', 'username' => 'branchhead3',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $headRole->id,
            'user_type' => 'branch_head', 'branch_id' => $branchA->id,
        ]);

        // Submitting branchB's id (not authorized) must fall back to the
        // one branch this head is actually authorized for, never branchB.
        $response = $this->actingAs($head)->get("http://localhost/dashboard/branch?branch_id={$branchB->id}");
        $response->assertStatus(200);
        $this->assertEquals($branchA->id, $response->viewData('filters')['branch_id']);
    }
}
