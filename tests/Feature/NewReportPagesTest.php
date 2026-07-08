<?php
// File: tests/Feature/NewReportPagesTest.php
// Purpose: Smoke-test the 4 new report pages (Contract Labour, PF/ESI, OT, LOP)
//          render successfully end-to-end for an authorized user.
// Author: System
// Date: 2026-07-08

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\ContractWorker;
use App\Models\ContractWorkerPayroll;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\PayrollRecord;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewReportPagesTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): User
    {
        $role = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'is_active' => true,
        ]);

        return User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'role_id' => $role->id,
        ]);
    }

    public function test_reports_index_shows_all_nine_report_cards(): void
    {
        $user = $this->actingSuperAdmin();

        $response = $this->actingAs($user)->get('http://localhost/reports');

        $response->assertStatus(200);
        $response->assertSee('Contract Labour Report');
        $response->assertSee('PF / ESI Report');
        $response->assertSee('OT Report');
        $response->assertSee('LOP Report');
    }

    public function test_contract_labour_report_page_renders(): void
    {
        $user = $this->actingSuperAdmin();

        $this->actingAs($user)->get('http://localhost/reports/contract-labour')->assertStatus(200);
    }

    public function test_pf_esi_report_page_renders(): void
    {
        $user = $this->actingSuperAdmin();

        $this->actingAs($user)->get('http://localhost/reports/pf-esi')->assertStatus(200);
    }

    public function test_overtime_report_page_renders(): void
    {
        $user = $this->actingSuperAdmin();

        $this->actingAs($user)->get('http://localhost/reports/overtime')->assertStatus(200);
    }

    public function test_lop_report_page_renders(): void
    {
        $user = $this->actingSuperAdmin();

        $this->actingAs($user)->get('http://localhost/reports/lop')->assertStatus(200);
    }

    public function test_pf_esi_and_lop_reports_show_real_payroll_data(): void
    {
        $user = $this->actingSuperAdmin();

        $branch = Branch::create(['name' => 'Head Office', 'code' => 'HO']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'Engineering']);
        $designation = Designation::create(['department_id' => $department->id, 'name' => 'Developer']);
        $employeeType = EmployeeType::create(['name' => 'Permanent', 'code' => 'PERM']);

        $employee = Employee::create([
            'employee_code' => 'EMP001',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employee_type_id' => $employeeType->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender' => 'female',
            'phone' => '9999999999',
            'date_of_joining' => now()->subYear(),
            'status' => 'active',
        ]);

        PayrollRecord::create([
            'employee_id' => $employee->id,
            'month' => now()->month,
            'year' => now()->year,
            'pf_employee' => 1800,
            'pf_employer' => 1800,
            'esi_employee' => 175,
            'esi_employer' => 475,
            'lop_days' => 2,
            'lop_deduction' => 2000,
            'ot_hours' => 5,
            'ot_amount' => 750,
            'net_salary' => 28000,
        ]);

        Attendance::create([
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'ot_minutes' => 120,
        ]);

        $pfEsi = $this->actingAs($user)->get('http://localhost/reports/pf-esi');
        $pfEsi->assertStatus(200);
        $pfEsi->assertSee('EMP001');
        $pfEsi->assertSee('1,800.00');

        $lop = $this->actingAs($user)->get('http://localhost/reports/lop');
        $lop->assertStatus(200);
        $lop->assertSee('EMP001');
        $lop->assertSee('2,000.00');

        $ot = $this->actingAs($user)->get('http://localhost/reports/overtime');
        $ot->assertStatus(200);
        $ot->assertSee('EMP001');
        $ot->assertSee('750.00');
    }

    public function test_contract_labour_report_shows_real_worker_data(): void
    {
        $user = $this->actingSuperAdmin();

        $contractor = Contractor::create(['name' => 'BuildRight Co', 'code' => 'BR001']);
        $worker = ContractWorker::create([
            'contractor_id' => $contractor->id,
            'name' => 'Ravi Kumar',
            'gender' => 'male',
            'wage_type' => 'daily',
            'wage_amount' => 600,
            'joining_date' => now()->subMonths(2),
            'status' => 'active',
        ]);

        ContractWorkerPayroll::create([
            'contractor_id' => $contractor->id,
            'contract_worker_id' => $worker->id,
            'month' => now()->month,
            'year' => now()->year,
            'total_days' => 26,
            'present_days' => 24,
            'absent_days' => 2,
            'gross_wages' => 14400,
            'deductions' => 400,
            'net_wages' => 14000,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get('http://localhost/reports/contract-labour');

        $response->assertStatus(200);
        $response->assertSee('Ravi Kumar');
        $response->assertSee('BuildRight Co');
        $response->assertSee('14,000.00');
    }
}
