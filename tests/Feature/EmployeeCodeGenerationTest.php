<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for Employee Code generation.
 *
 * Employee Code is auto-generated at employee creation (EmployeeController::
 * store()) via the single 5-step wizard (Personal/Contact/Address/Employee
 * Information/Statutory Details). The update()-time generation is kept
 * purely as a backfill path for employees that predate this behavior and
 * still have a blank employee_code; it never regenerates a code that's
 * already set.
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
        $shift = Shift::create(['name' => "General Shift $seq", 'code' => "GEN$seq", 'is_active' => true]);

        return [$branch, $department, $shift];
    }

    /** Full 5-step wizard payload — every field the current rules() requires. */
    private function wizardPayload(Department $department, Shift $shift, array $overrides = []): array
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
            'employee_category'       => 'staff',
            'department_id'           => $department->id,
            'designation'             => 'Machine Operator',
            'date_of_joining'         => now()->toDateString(),
            'shift_id'                => $shift->id,
            'is_pf_applicable'        => 'yes',
            'is_esi_applicable'       => 'yes',
            'is_tds_applicable'       => 'no',
            'is_earnings_applicable'  => 'yes',
        ], $overrides);
    }

    public function test_employee_code_is_generated_immediately_on_creation_with_no_further_steps(): void
    {
        $admin = $this->superAdmin();
        [$branch, $department, $shift] = $this->branchAndDepartment();
        $this->withSession(['current_branch_id' => $branch->id]);

        $response = $this->actingAs($admin)->post('http://localhost/employees', $this->wizardPayload($department, $shift, [
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
        [$branch, $department, $shift] = $this->branchAndDepartment();
        $this->withSession(['current_branch_id' => $branch->id]);

        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $this->actingAs($admin)->post('http://localhost/employees', $this->wizardPayload($department, $shift, [
                'branch_id'       => $branch->id,
                'official_email'  => $email,
                'biometric_id'    => uniqid(),
            ]))->assertRedirect(route('employees.index'));
        }

        $codes = Employee::orderBy('id')->pluck('employee_code')->all();
        $this->assertSame(['EMP0001', 'EMP0002', 'EMP0003'], $codes);
        $this->assertSame(3, count(array_unique($codes)), 'Employee Codes must be unique — duplicates were generated.');
    }

    public function test_employee_code_uses_employee_type_prefix_when_employee_type_id_set(): void
    {
        $admin = $this->superAdmin();
        [$branch, $department, $shift] = $this->branchAndDepartment();
        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);
        $this->withSession(['current_branch_id' => $branch->id]);

        $response = $this->actingAs($admin)->post('http://localhost/employees', $this->wizardPayload($department, $shift, [
            'branch_id'        => $branch->id,
            'employee_type_id' => $employeeType->id,
        ]));

        $response->assertRedirect(route('employees.index'));
        $employee = Employee::where('official_email', 'anitha123@example.com')->firstOrFail();

        $this->assertNotNull($employee->employee_code);
        $this->assertSame('staff', $employee->primary_employee_type);
    }

    public function test_employee_code_is_never_regenerated_on_a_later_update(): void
    {
        $admin = $this->superAdmin();
        [$branch, $department, $shift] = $this->branchAndDepartment();
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

        $this->actingAs($admin)->put("http://localhost/employees/{$employee->id}", $this->wizardPayload($department, $shift, [
            'branch_id'      => $branch->id,
            'official_email' => $employee->official_email,
        ]));

        $employee->refresh();
        $this->assertSame('PE0007', $employee->employee_code);
    }

    public function test_employee_code_sequence_increments_per_employee_type_prefix(): void
    {
        $admin = $this->superAdmin();
        [$branch, $department, $shift] = $this->branchAndDepartment();
        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);
        $this->withSession(['current_branch_id' => $branch->id]);

        foreach (['one@example.com', 'two@example.com'] as $email) {
            $this->actingAs($admin)->post('http://localhost/employees', $this->wizardPayload($department, $shift, [
                'branch_id'        => $branch->id,
                'official_email'   => $email,
                'biometric_id'     => uniqid(),
                'employee_type_id' => $employeeType->id,
            ]))->assertRedirect(route('employees.index'));
        }

        $codes = Employee::orderBy('id')->pluck('employee_code')->all();
        $this->assertSame(['PE0001', 'PE0002'], $codes);
    }
}
