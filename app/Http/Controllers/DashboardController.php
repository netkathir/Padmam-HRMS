<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $month = now()->month;
        $year  = now()->year;

        // Core stats
        $totalEmployees   = Employee::active()->count();
        $presentToday     = Attendance::where('date', $today)->whereIn('status', ['present', 'half_day'])->count();
        $pendingLeaves    = LeaveRequest::where('status', 'pending')->count();
        $absentToday      = $totalEmployees - $presentToday;

        // Monthly payroll summary
        $payrollMonth = PayrollRecord::where('month', $month)->where('year', $year)
            ->selectRaw('COUNT(*) as count, SUM(net_salary) as total, SUM(gross_earnings) as gross')
            ->first();

        // Today's attendance breakdown
        $attendanceBreakdown = Attendance::where('date', $today)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Upcoming birthdays (next 7 days)
        $birthdays = Employee::active()
            ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')")
            ->select('id', 'first_name', 'last_name', 'date_of_birth', 'department_id')
            ->with('department:id,name')
            ->limit(5)
            ->get();

        // Work anniversaries (next 7 days)
        $anniversaries = Employee::active()
            ->whereRaw("DATE_FORMAT(date_of_joining, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')")
            ->select('id', 'first_name', 'last_name', 'date_of_joining', 'department_id')
            ->with('department:id,name')
            ->limit(5)
            ->get();

        // Department-wise headcount
        $deptWise = Employee::active()
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as dept, COUNT(*) as cnt')
            ->groupBy('departments.name')
            ->orderByDesc('cnt')
            ->limit(8)
            ->get();

        // Recent leave requests
        $recentLeaves = LeaveRequest::with(['employee:id,first_name,last_name', 'leaveType:id,name'])
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
