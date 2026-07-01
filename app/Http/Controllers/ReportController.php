<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Contractor;
use App\Models\ContractWorkerPayroll;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Models\Department;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function attendance(Request $request)
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate   = $request->input('to_date', now()->toDateString());

        $query = Attendance::with(['employee.department', 'employee.branch'])
            ->whereBetween('date', [$fromDate, $toDate])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('date')->orderBy('employee_id');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'attendance');
        }

        $records    = $query->paginate(30)->withQueryString();

        // Summary — use the same filtered query (without pagination)
        $summaryQuery = Attendance::whereBetween('date', [$fromDate, $toDate])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status));

        $summary = $summaryQuery
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $employees   = Employee::active()->orderBy('first_name')->get();
        $departments = Department::orderBy('name')->get();

        return view('reports.attendance', compact('records', 'summary', 'employees', 'departments', 'fromDate', 'toDate'));
    }

    public function employees(Request $request)
    {
        $query = Employee::with(['branch', 'department', 'designation', 'employeeType', 'currentSalary'])
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('designation_id'), fn($q) => $q->where('designation_id', $request->designation_id))
            ->when($request->filled('employee_type_id'), fn($q) => $q->where('employee_type_id', $request->employee_type_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('first_name');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'employees');
        }

        $employees = $query->paginate(30)->withQueryString();

        // Stats — respect the same filters as the table
        $stats = [];
        $baseQuery = Employee::query()
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('designation_id'), fn($q) => $q->where('designation_id', $request->designation_id))
            ->when($request->filled('employee_type_id'), fn($q) => $q->where('employee_type_id', $request->employee_type_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status));

        $stats['total']         = (clone $baseQuery)->count();
        $stats['active']        = (clone $baseQuery)->where('status', 'active')->count();
        $stats['on_leave']      = Attendance::where('date', now()->toDateString())->where('status', 'on_leave')
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('designation_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('designation_id', $request->designation_id)))
            ->when($request->filled('employee_type_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('employee_type_id', $request->employee_type_id)))
            ->count();
        $stats['new_this_month'] = (clone $baseQuery)
            ->whereYear('date_of_joining', now()->year)
            ->whereMonth('date_of_joining', now()->month)
            ->count();

        $departments   = Department::orderBy('name')->get();
        $branches      = \App\Models\Branch::orderBy('name')->get();
        $designations  = \App\Models\Designation::orderBy('name')->get();
        $employeeTypes = \App\Models\EmployeeType::orderBy('name')->get();

        return view('reports.employees', compact('employees', 'stats', 'departments', 'branches', 'designations', 'employeeTypes'));
    }

    public function leave(Request $request)
    {
        $year = $request->input('year', now()->year);
        $query = LeaveRequest::with(['employee.department', 'leaveType'])
            ->whereYear('start_date', $year)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('leave_type_id'), fn($q) => $q->where('leave_type_id', $request->leave_type_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderByDesc('start_date');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'leave');
        }

        $leaves      = $query->paginate(30)->withQueryString();
        $leaveTypes  = \App\Models\LeaveType::where('is_active', true)->get();
        $departments = Department::orderBy('name')->get();

        return view('reports.leave', compact('leaves', 'leaveTypes', 'departments', 'year'));
    }

    public function payroll(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $query = PayrollRecord::with(['employee.department'])
            ->where('month', $month)->where('year', $year)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderBy('employee_id');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'payroll');
        }

        $payrollRecords = $query->paginate(30)->withQueryString();

        // Summary — same filters as records query
        $summaryQuery = PayrollRecord::where('month', $month)->where('year', $year)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)));

        $totals = $summaryQuery
            ->selectRaw('COUNT(*) as count, SUM(gross_earnings) as gross, SUM(total_deductions) as deductions, SUM(net_salary) as net')
            ->first();

        $departments = Department::orderBy('name')->get();

        return view('reports.payroll', compact('payrollRecords', 'totals', 'departments', 'month', 'year'));
    }

    public function contractor(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $contractors = Contractor::withCount('contractWorkers')->orderBy('name')->get();

        $payrollSummary = ContractWorkerPayroll::where('month', $month)->where('year', $year)
            ->selectRaw('contractor_id, COUNT(*) as worker_count, SUM(gross_wages) as total_gross, SUM(net_wages) as total_net')
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count")
            ->groupBy('contractor_id')
            ->get()
            ->keyBy('contractor_id');

        return view('reports.contractor', compact('contractors', 'payrollSummary', 'month', 'year'));
    }

    private function exportCsv($records, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = $type . '-report-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($records, $type) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->getCsvHeaders($type));

            foreach ($records as $record) {
                fputcsv($handle, $this->getCsvRow($record, $type));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function getCsvHeaders(string $type): array
    {
        return match ($type) {
            'attendance' => ['Date', 'Employee', 'Department', 'First In', 'Last Out', 'Work Hours', 'Status'],
            'employees'  => ['Code', 'Name', 'Department', 'Designation', 'Branch', 'Type', 'Joining Date', 'Status'],
            'leave'      => ['Employee', 'Department', 'Leave Type', 'From', 'To', 'Days', 'Status'],
            'payroll'    => ['Employee', 'Department', 'Basic', 'Gross', 'Deductions', 'Net Salary', 'Status'],
            default      => [],
        };
    }

    private function getCsvRow($record, string $type): array
    {
        return match ($type) {
            'attendance' => [
                $record->date->format('d-m-Y'),
                optional($record->employee)->full_name,
                optional(optional($record->employee)->department)->name,
                $record->first_in,
                $record->last_out,
                $record->work_hours,
                $record->status,
            ],
            'employees'  => [
                $record->employee_code,
                $record->full_name,
                optional($record->department)->name,
                optional($record->designation)->name,
                optional($record->branch)->name,
                optional($record->employeeType)->name,
                optional($record->date_of_joining)->format('d-m-Y'),
                $record->status,
            ],
            'leave'      => [
                optional($record->employee)->full_name,
                optional(optional($record->employee)->department)->name,
                optional($record->leaveType)->name,
                optional($record->start_date)->format('d-m-Y'),
                optional($record->end_date)->format('d-m-Y'),
                $record->total_days,
                $record->status,
            ],
            'payroll'    => [
                optional($record->employee)->full_name,
                optional(optional($record->employee)->department)->name,
                $record->basic_salary,
                $record->gross_earnings,
                $record->total_deductions,
                $record->net_salary,
                $record->status,
            ],
            default      => [],
        };
    }
}
