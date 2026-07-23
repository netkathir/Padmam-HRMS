<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\SalarySlab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalMasterDataBugsTest extends TestCase
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

    public function test_salary_slab_can_be_created_and_persists_real_values(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/salary-slabs', [
            'name' => 'Band A',
            'tds_percentage' => 0, 'pf_employee_percentage' => 0, 'pf_employer_percentage' => 0,
            'esi_employee_percentage' => 0, 'esi_employer_percentage' => 0,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.salary-slabs.index'));
        $this->assertDatabaseHas('salary_slabs', [
            'name' => 'Band A',
        ]);
    }

    public function test_leave_type_can_be_created_and_persists_real_values(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/leave-types', [
            'name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 12,
            'is_carry_forward' => '1', 'max_carry_forward' => 5, 'is_paid' => '1',
            'is_half_day_allowed' => '1', 'gender_specific' => 'all', 'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.leave-types.index'));
        $type = LeaveType::where('code', 'CL')->firstOrFail();
        $this->assertEquals(12, (float) $type->days_per_year);
        $this->assertTrue($type->is_carry_forward);
    }

    public function test_leave_type_destroy_checks_leave_requests_relation(): void
    {
        $admin = $this->superAdmin();
        $type = LeaveType::create(['name' => 'Sick Leave', 'code' => 'SL', 'days_per_year' => 10, 'gender_specific' => 'all', 'is_active' => true]);

        // Must not throw "call to undefined method leaveRequests()".
        $response = $this->actingAs($admin)->delete("http://localhost/masters/leave-types/{$type->id}");
        $response->assertRedirect(route('masters.leave-types.index'));
        $this->assertDatabaseMissing('leave_types', ['id' => $type->id]);
    }

    public function test_earnings_component_can_be_created_and_persists_real_values(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post('http://localhost/masters/earnings', [
            'name' => 'HRA', 'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.earnings.index'));
        $this->assertDatabaseHas('earnings_components', ['name' => 'HRA']);
    }

    private function makeEmployee(): Employee
    {
        static $seq = 0;
        $seq++;
        $branch = \App\Models\Branch::create(['name' => "Branch $seq", 'code' => "BR$seq", 'is_active' => true]);
        $department = Department::create(['name' => "Dept $seq", 'branch_id' => $branch->id, 'is_active' => true]);
        $designation = Designation::create(['name' => "Desig $seq", 'department_id' => $department->id, 'is_active' => true]);
        $employeeType = EmployeeType::firstOrCreate(['name' => 'Permanent'], ['code' => 'PERM', 'is_active' => true]);

        return Employee::create([
            'employee_code' => "EMP$seq", 'branch_id' => $branch->id, 'department_id' => $department->id,
            'designation_id' => $designation->id, 'employee_type_id' => $employeeType->id,
            'first_name' => 'Test', 'last_name' => "Employee$seq", 'date_of_birth' => '1990-01-01',
            'gender' => 'male', 'official_email' => "emp$seq@example.com", 'phone' => '9999999999',
            'date_of_joining' => now()->toDateString(), 'status' => 'active',
        ]);
    }

    public function test_manual_attendance_entry_and_approval_flow_does_not_throw(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->makeEmployee();

        $response = $this->actingAs($admin)->post('http://localhost/attendance/manual', [
            'employee_id' => $employee->id,
            'date' => now()->subDay()->toDateString(),
            'in_time' => '09:00',
            'out_time' => '18:00',
            'status' => 'present',
            'manual_reason' => 'Biometric device was down',
        ]);

        $response->assertRedirect(route('attendance.pending'));
        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertTrue($attendance->is_manual_entry);
        $this->assertEquals('pending', $attendance->approval_status);
        $this->assertEquals('Biometric device was down', $attendance->remarks);

        // Pending queue must not throw (was querying a nonexistent column before).
        $this->actingAs($admin)->get('http://localhost/attendance/pending')->assertStatus(200);

        // Approval must not throw and must stamp approval fields correctly.
        $this->actingAs($admin)->post("http://localhost/attendance/{$attendance->id}/approve", ['action' => 'approve'])
            ->assertRedirect();
        $attendance->refresh();
        $this->assertEquals('approved', $attendance->approval_status);
        $this->assertNotNull($attendance->approved_at);
    }

    public function test_users_status_filter_actually_filters(): void
    {
        $role = Role::firstOrCreate(['name' => 'employee'], ['display_name' => 'Employee', 'is_active' => true]);
        $admin = $this->superAdmin();

        User::create(['name' => 'Active One', 'email' => 'active@example.com', 'username' => 'activeone', 'password' => bcrypt('x'), 'is_active' => true, 'role_id' => $role->id]);
        User::create(['name' => 'Inactive One', 'email' => 'inactive@example.com', 'username' => 'inactiveone', 'password' => bcrypt('x'), 'is_active' => false, 'role_id' => $role->id]);

        $response = $this->actingAs($admin)->get('http://localhost/users?is_active=1');
        $response->assertSee('Active One');
        $response->assertDontSee('Inactive One');

        $response2 = $this->actingAs($admin)->get('http://localhost/users?is_active=0');
        $response2->assertSee('Inactive One');
        $response2->assertDontSee('Active One');
    }

    public function test_leave_cancellation_stamps_cancelled_by_and_at(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['name' => 'Casual', 'code' => 'CL', 'days_per_year' => 12, 'gender_specific' => 'all', 'is_active' => true]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5)->toDateString(), 'end_date' => now()->addDays(6)->toDateString(),
            'total_days' => 2, 'status' => 'pending', 'applied_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post("http://localhost/leaves/{$leave->id}/cancel");
        $response->assertRedirect();

        $leave->refresh();
        $this->assertEquals('cancelled', $leave->status);
        $this->assertEquals($admin->id, $leave->cancelled_by);
        $this->assertNotNull($leave->cancelled_at);
    }
}
