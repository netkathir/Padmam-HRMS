<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Support\BranchScope;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $month = now()->month;
        $year  = now()->year;

        // Core stats — every query below is branch-scoped: a no-op for
        // unscoped Super Admin / legacy accounts, and restricted to the
        // effective branch for a branch-scoped user or a Super Admin who
        // has switched via the Branch Switcher.
        $totalEmployees   = BranchScope::scopeQuery(Employee::active())->count();
        $presentToday     = BranchScope::scopeQueryVia(Attendance::where('date', $today)->whereIn('status', ['present', 'half_day']), 'employee')->count();
        $pendingLeaves    = BranchScope::scopeQueryVia(LeaveRequest::where('status', 'pending'), 'employee')->count();
        $absentToday      = $totalEmployees - $presentToday;

        // Monthly payroll summary
        $payrollMonth = BranchScope::scopeQueryVia(
            PayrollRecord::where('month', $month)->where('year', $year), 'employee'
        )
            ->selectRaw('COUNT(*) as count, SUM(net_salary) as total, SUM(gross_earnings) as gross')
            ->first();

        // Today's attendance breakdown
        $attendanceBreakdown = BranchScope::scopeQueryVia(Attendance::where('date', $today), 'employee')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $startDate = now()->startOfDay();
        $endDate = now()->copy()->addDays(7)->endOfDay();
        $upcomingDayCodes = collect(range(0, 7))
            ->map(fn ($offset) => now()->copy()->addDays($offset)->format('m-d'))
            ->all();

        $upcomingEmployees = BranchScope::scopeQuery(Employee::active())
            ->select('id', 'first_name', 'last_name', 'date_of_birth', 'date_of_joining', 'department_id')
            ->with('department:id,name')
            ->get();

        $birthdays = $upcomingEmployees
            ->filter(function ($employee) use ($upcomingDayCodes): bool {
                return $employee->date_of_birth
                    && in_array($employee->date_of_birth->format('m-d'), $upcomingDayCodes, true);
            })
            ->take(5)
            ->values();

        $anniversaries = $upcomingEmployees
            ->filter(function ($employee) use ($upcomingDayCodes): bool {
                return $employee->date_of_joining
                    && in_array($employee->date_of_joining->format('m-d'), $upcomingDayCodes, true);
            })
            ->take(5)
            ->values();

        // Department-wise headcount
        $deptWise = BranchScope::scopeQuery(Employee::active(), 'employees.branch_id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as dept, COUNT(*) as cnt')
            ->groupBy('departments.name')
            ->orderByDesc('cnt')
            ->limit(8)
            ->get();

        // Recent leave requests
        $recentLeaves = BranchScope::scopeQueryVia(LeaveRequest::query(), 'employee')
            ->with(['employee:id,first_name,last_name', 'leaveType:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'totalEmployees', 'presentToday', 'pendingLeaves', 'absentToday',
            'payrollMonth', 'attendanceBreakdown',
            'birthdays', 'anniversaries', 'deptWise', 'recentLeaves',
            'today', 'month', 'year'
        ));
    }
}
