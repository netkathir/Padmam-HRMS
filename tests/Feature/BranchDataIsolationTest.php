<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchDataIsolationTest extends TestCase
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
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);
    }

    private function branchHead(Branch $branch): User
    {
        $role = $this->makeRole('branch_head', ['applicable_user_types' => ['branch_head']]);

        return User::create([
            'name' => 'Branch Head',
            'email' => 'head@example.com',
            'username' => 'branchhead',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
            'user_type' => 'branch_head',
            'branch_id' => $branch->id,
        ]);
    }

    private function makeEmployee(Branch $branch, Department $department, Designation $designation, array $overrides = []): Employee
    {
        static $seq = 0;
        $seq++;

        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);

        return Employee::create(array_merge([
            'employee_code' => "EMP$seq",
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employee_type_id' => $employeeType->id,
            'first_name' => 'Test',
            'last_name' => "Employee$seq",
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'official_email' => "emp$seq@example.com",
            'phone' => '9999999999',
            'date_of_joining' => now()->toDateString(),
            'status' => 'active',
        ], $overrides));
    }

    public function test_attendance_status_summary_is_scoped_to_current_branch(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $deptA = Department::create(['name' => 'Dept A', 'branch_id' => $branchA->id, 'is_active' => true]);
        $deptB = Department::create(['name' => 'Dept B', 'branch_id' => $branchB->id, 'is_active' => true]);
        $desigA = Designation::create(['name' => 'Desig A', 'department_id' => $deptA->id, 'is_active' => true]);
        $desigB = Designation::create(['name' => 'Desig B', 'department_id' => $deptB->id, 'is_active' => true]);

        $empA = $this->makeEmployee($branchA, $deptA, $desigA);
        $empB = $this->makeEmployee($branchB, $deptB, $desigB);

        $date = now()->toDateString();
        // Inserted via the query builder (not Eloquent's `date` cast) so the
        // stored value is the bare "Y-m-d" string the controller's
        // `where('date', $date)` expects — Eloquent's date cast serializes
        // to "Y-m-d H:i:s" for storage, which a real DATE column (MySQL)
        // truncates automatically but SQLite (used here) stores verbatim.
        \Illuminate\Support\Facades\DB::table('attendance')->insert([
            ['employee_id' => $empA->id, 'date' => $date, 'status' => 'present', 'created_at' => now(), 'updated_at' => now()],
            ['employee_id' => $empB->id, 'date' => $date, 'status' => 'absent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $head = $this->branchHead($branchA);

        $response = $this->actingAs($head)->get("http://localhost/attendance?date={$date}");
        $response->assertStatus(200);

        // The status-count summary widget must only reflect branch A's
        // attendance (1 present), not branch B's (1 absent) — previously
        // this raw aggregate query had no branch filter at all.
        $summary = $response->viewData('summary');
        $this->assertEquals(1, $summary['present'] ?? 0);
        $this->assertArrayNotHasKey('absent', $summary->toArray());
    }

    public function test_contract_attendance_dropdown_excludes_other_branch_contractors(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();

        $contractorA = Contractor::create(['name' => 'Contractor A', 'code' => 'CA1', 'is_active' => true, 'branch_id' => $branchA->id]);
        $contractorB = Contractor::create(['name' => 'Contractor B', 'code' => 'CB1', 'is_active' => true, 'branch_id' => $branchB->id]);

        $head = $this->branchHead($branchA);

        $response = $this->actingAs($head)->get('http://localhost/contract-attendance');
        $response->assertStatus(200);
        $response->assertSee('Contractor A');
        $response->assertDontSee('Contractor B');
    }

    public function test_contract_attendance_blocks_cross_branch_contractor_id(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();

        Contractor::create(['name' => 'Contractor A', 'code' => 'CA1', 'is_active' => true, 'branch_id' => $branchA->id]);
        $contractorB = Contractor::create(['name' => 'Contractor B', 'code' => 'CB1', 'is_active' => true, 'branch_id' => $branchB->id]);

        $head = $this->branchHead($branchA);

        // Previously ContractAttendanceController never verified the
        // requested contractor_id belonged to the actor's branch.
        $this->actingAs($head)
            ->get("http://localhost/contract-attendance?contractor_id={$contractorB->id}")
            ->assertStatus(403);
    }

    public function test_employee_designation_from_another_branch_is_rejected(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();

        $deptA = Department::create(['name' => 'Dept A', 'branch_id' => $branchA->id, 'is_active' => true]);
        $deptB = Department::create(['name' => 'Dept B', 'branch_id' => $branchB->id, 'is_active' => true]);
        $desigB = Designation::create(['name' => 'Desig B', 'department_id' => $deptB->id, 'is_active' => true]);
        $employeeType = EmployeeType::create(['name' => 'Permanent', 'code' => 'PERM', 'is_active' => true]);

        $admin = $this->superAdmin();
        // Auto-resolves current branch to branchA (first by id).
        $this->actingAs($admin)->get('http://localhost/employees');

        $response = $this->actingAs($admin)->post('http://localhost/employees', [
            'employee_code' => 'EMP99',
            'branch_id' => $branchA->id,
            'department_id' => $deptA->id,
            // Designation belongs to branch B's department — must be rejected
            // even though department_id itself matches branch A.
            'designation_id' => $desigB->id,
            'employee_type_id' => $employeeType->id,
            'first_name' => 'Cross',
            'last_name' => 'Branch',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'official_email' => 'crossbranch@example.com',
            'phone' => '9999999999',
            'date_of_joining' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('designation_id');
        $this->assertDatabaseMissing('employees', ['official_email' => 'crossbranch@example.com']);
    }

    public function test_employee_create_form_locks_branch_field_for_branch_head(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $head = $this->branchHead($branchA);

        $response = $this->actingAs($head)->get('http://localhost/employees/create');
        $response->assertStatus(200);
        $response->assertSee($branchA->name);
        $response->assertDontSee($branchB->name);
    }
}
