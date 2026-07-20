<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for Employee Code generation.
 *
 * Employee Code is now auto-generated at employee creation itself
 * (EmployeeController::store()) — no Employee Slab step required. The
 * update()-time generation (Employee Slab's Employment Information save)
 * is kept purely as a backfill path for employees that predate this
 * behavior and still have a blank employee_code; it never regenerates a
 * code that's already set.
 */
class EmployeeCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin', 'is_active' => true]);

        return User::create([
            'name' => 'Super Admin', 'email' => 'super@example.com', 'username' => 'superadmin',
            'password' => bcrypt('password'), 'is_active' => true, 'role_id' => $role->id,
        ]);
    }

    private function branchAndDepartment(): array
    {
        static $seq = 0;
        $seq++;
        $branch = Branch::create(['name' => "Main Branch $seq", 'code' => "MB$seq", 'is_active' => true]);
        $department = Department::create(['name' => "Manufacture $seq", 'code' => "MFG$seq", 'branch_id' => $branch->id, 'is_active' => true]);

        return [$branch, $department];
    }

    private function stepsOneToThreePayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'              => 'Anitha',
            'last_name'               => null,
            'display_name'            => 'Anitha',
            'date_of_birth'           => '1996-06-29',
            'gender'                  => 'female',
            'nationality'             => 'Indian',
            'biometric_id'            => '78764567',
            'official_email'          => 'anitha123@example.com',
            'phone'                   => '9098789798',
            'address_line1'           => 'No 16 Kovil street',
            'district'                => 'Villupuram',
            'state'                   => 'Andhra Pradesh',
            'pincode'                 => '604303',
            'same_as_current_address' => '1',
        ], $overrides);
    }

    public function test_employee_code_is_generated_immediately_on_creation_with_no_further_steps(): void
    {
        $admin = $this->superAdmin();
        [$branch] = $this->branchAndDepartment();
        $this->withSession(['current_branch_id' => $branch->id]);

        $response = $this->actingAs($admin)->post('http://localhost/employees', $this->stepsOneToThreePayload([
            'branch_id' => $branch->id,
        ]));

        $response->assertRedirect(route('employees.index'));
        $employee = Employee::where('official_email', 'anitha123@example.com')->firstOrFail();

        $this->assertNotNull($employee->employee_code, 'Employee Code was not generated automatically at creation.');
        $this->assertSame('EMP0001', $employee->employee_code);
    }

    public function test_employee_code_sequence_increments_across_consecutively_created_employees(): void
    {
        $admin = $this->superAdmin();
        [$branch] = $this->branchAndDepartment();
        $this->withSession(['current_branch_id' => $branch->id]);

        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $this->actingAs($admin)->post('http://localhost/employees', $this->stepsOneToThreePayload([
                'branch_id'       => $branch->id,
                'official_email'  => $email,
                'biometric_id'    => uniqid(),
            ]))->assertRedirect(route('employees.index'));
        }

        $codes = Employee::orderBy('id')->pluck('employee_code')->all();
        $this->assertSame(['EMP0001', 'EMP0002', 'EMP0003'], $codes);
        $this->assertSame(3, count(array_unique($codes)), 'Employee Codes must be unique — duplicates were generated.');
    }

    public function test_employee_code_is_auto_generated_when_employee_slab_employment_info_is_saved(): void
    {
        $admin = $this->superAdmin();
        [$branch, $department] = $this->branchAndDepartment();
        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);
        $this->withSession(['current_branch_id' => $branch->id]);

        $employee = Employee::create([
            'branch_id' => $branch->id, 'first_name' => 'Anitha', 'last_name' => null,
            'display_name' => 'Anitha', 'date_of_birth' => '1996-06-29', 'gender' => 'female',
            'official_email' => 'anitha123@example.com', 'phone' => '9098789798',
            'address_line1' => 'No 16 Kovil street', 'district' => 'Villupuram',
            'state' => 'Andhra Pradesh', 'pincode' => '604303', 'biometric_id' => '78764567',
            'status' => 'active',
        ]);
        $this->assertNull($employee->employee_code);

        $response = $this->actingAs($admin)->put("http://localhost/employees/{$employee->id}", $this->stepsOneToThreePayload([
            'branch_id'          => $branch->id,
            'official_email'     => $employee->official_email,
            'employee_category'  => 'staff',
            'department_id'      => $department->id,
            'designation'        => 'Machine Operator',
            'employee_type_id'   => $employeeType->id,
            'date_of_joining'    => now()->toDateString(),
            'status'             => 'active',
        ]));

        $response->assertRedirect(route('employee-slab.edit', $employee));
        $employee->refresh();

        $this->assertNotNull($employee->employee_code, 'Employee Code was not auto-generated when Employment Information was saved.');
        $this->assertSame('PE0001', $employee->employee_code);
        $this->assertSame('staff', $employee->primary_employee_type);
    }

    public function test_employee_code_is_never_regenerated_on_a_later_employment_info_save(): void
    {
        $admin = $this->superAdmin();
        [$branch, $department] = $this->branchAndDepartment();
        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);
        $this->withSession(['current_branch_id' => $branch->id]);

        $employee = Employee::create([
            'branch_id' => $branch->id, 'employee_code' => 'PE0007',
            'first_name' => 'Anitha', 'last_name' => null, 'display_name' => 'Anitha',
            'date_of_birth' => '1996-06-29', 'gender' => 'female',
            'official_email' => 'anitha123@example.com', 'phone' => '9098789798',
            'address_line1' => 'No 16 Kovil street', 'district' => 'Villupuram',
            'state' => 'Andhra Pradesh', 'pincode' => '604303', 'biometric_id' => '78764567',
            'department_id' => $department->id, 'employee_type_id' => $employeeType->id,
            'primary_employee_type' => 'staff', 'status' => 'active',
        ]);

        $this->actingAs($admin)->put("http://localhost/employees/{$employee->id}", $this->stepsOneToThreePayload([
            'branch_id'          => $branch->id,
            'official_email'     => $employee->official_email,
            'employee_category'  => 'staff',
            'department_id'      => $department->id,
            'designation'        => 'Machine Operator',
            'employee_type_id'   => $employeeType->id,
            'date_of_joining'    => now()->toDateString(),
            'status'             => 'inactive',
        ]));

        $employee->refresh();
        $this->assertSame('PE0007', $employee->employee_code);
        $this->assertSame('inactive', $employee->status);
    }

    public function test_employee_code_sequence_increments_per_employee_type_prefix(): void
    {
        $admin = $this->superAdmin();
        [$branch, $department] = $this->branchAndDepartment();
        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);
        $this->withSession(['current_branch_id' => $branch->id]);

        foreach (['one@example.com', 'two@example.com'] as $email) {
            $employee = Employee::create([
                'branch_id' => $branch->id, 'first_name' => 'Test', 'last_name' => null,
                'display_name' => 'Test', 'date_of_birth' => '1996-06-29', 'gender' => 'female',
                'official_email' => $email, 'phone' => '9098789798',
                'address_line1' => 'No 16 Kovil street', 'district' => 'Villupuram',
                'state' => 'Andhra Pradesh', 'pincode' => '604303', 'biometric_id' => uniqid(),
                'status' => 'active',
            ]);

            $this->actingAs($admin)->put("http://localhost/employees/{$employee->id}", $this->stepsOneToThreePayload([
                'branch_id'          => $branch->id,
                'official_email'     => $email,
                'biometric_id'       => $employee->biometric_id,
                'employee_category'  => 'staff',
                'department_id'      => $department->id,
                'designation'        => 'Machine Operator',
                'employee_type_id'   => $employeeType->id,
                'date_of_joining'    => now()->toDateString(),
                'status'             => 'active',
            ]));
        }

        $codes = Employee::orderBy('id')->pluck('employee_code')->all();
        $this->assertSame(['PE0001', 'PE0002'], $codes);
    }
}
