<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ContractWorkerPayroll;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Models\Department;
use App\Support\BranchScope;
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

        $query = BranchScope::scopeQueryVia(
            Attendance::with(['employee.department', 'employee.branch'])
                ->whereBetween('date', [$fromDate, $toDate]),
            'employee'
        )
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('contractor_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('contractor_id', $request->contractor_id)))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('date')->orderBy('employee_id');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'attendance');
        }

        $records    = $query->paginate(30)->withQueryString();

        // Summary — use the same filtered query (without pagination)
        $summaryQuery = BranchScope::scopeQueryVia(
            Attendance::whereBetween('date', [$fromDate, $toDate]), 'employee'
        )
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('contractor_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('contractor_id', $request->contractor_id)))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status));

        $summary = $summaryQuery
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $employees   = BranchScope::scopeQuery(Employee::active()->orderBy('first_name'))->get();
        $departments = Department::orderBy('name')->get();

        return view('reports.attendance', compact('records', 'summary', 'employees', 'departments', 'fromDate', 'toDate'));
    }

    public function employees(Request $request)
    {
        $query = BranchScope::scopeQuery(
            Employee::with(['branch', 'department', 'designation', 'employeeType', 'currentSalary'])
        )
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
        $baseQuery = BranchScope::scopeQuery(Employee::query())
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('designation_id'), fn($q) => $q->where('designation_id', $request->designation_id))
            ->when($request->filled('employee_type_id'), fn($q) => $q->where('employee_type_id', $request->employee_type_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status));

        $stats['total']         = (clone $baseQuery)->count();
        $stats['active']        = (clone $baseQuery)->where('status', 'active')->count();
        $stats['on_leave']      = BranchScope::scopeQueryVia(
            Attendance::where('date', now()->toDateString())->where('status', 'on_leave'), 'employee'
        )
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
        $query = BranchScope::scopeQueryVia(
            LeaveRequest::with(['employee.department', 'leaveType'])
                ->whereYear('start_date', $year),
            'employee'
        )
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

        $query = BranchScope::scopeQueryVia(
            PayrollRecord::with(['employee.department'])
                ->where('month', $month)->where('year', $year),
            'employee'
        )
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('contractor_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('contractor_id', $request->contractor_id)))
            ->orderBy('employee_id');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'payroll');
        }

        $payrollRecords = $query->paginate(30)->withQueryString();

        // Summary — same filters as records query
        $summaryQuery = BranchScope::scopeQueryVia(
            PayrollRecord::where('month', $month)->where('year', $year), 'employee'
        )
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('contractor_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('contractor_id', $request->contractor_id)));

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

        $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $scopedBranchId = BranchScope::currentBranchId();

        // Only count workers who were actually assigned to the contractor during the
        // selected month — joined on/before the period end and not exited before it started.
        $contractors = BranchScope::scopeQuery(Contractor::withCount(['employees' => function ($q) use ($periodStart, $periodEnd, $scopedBranchId) {
            $q->where('date_of_joining', '<=', $periodEnd)
                ->where(function ($q2) use ($periodStart) {
                    $q2->whereDoesntHave('exitRecord')
                        ->orWhereHas('exitRecord', fn($e) => $e->where('exit_date', '>=', $periodStart));
                })
                ->when($scopedBranchId !== null, fn($q3) => $q3->where('branch_id', $scopedBranchId));
        }]))->orderBy('name')->get();

        $payrollSummary = PayrollRecord::query()
            ->join('employees', 'payroll_records.employee_id', '=', 'employees.id')
            ->whereNotNull('employees.contractor_id')
            ->where('payroll_records.month', $month)
            ->where('payroll_records.year', $year)
            ->when($scopedBranchId !== null, fn($q) => $q->where('employees.branch_id', $scopedBranchId))
            ->selectRaw('employees.contractor_id as contractor_id, COUNT(*) as worker_count, SUM(payroll_records.gross_earnings) as total_gross, SUM(payroll_records.net_salary) as total_net')
            ->selectRaw("SUM(CASE WHEN payroll_records.status = 'paid' THEN 1 ELSE 0 END) as paid_count")
            ->groupBy('employees.contractor_id')
            ->get()
            ->keyBy('contractor_id');

        return view('reports.contractor', compact('contractors', 'payrollSummary', 'month', 'year'));
    }

    public function contractLabour(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $query = BranchScope::scopeQueryVia(
            ContractWorkerPayroll::with(['worker', 'contractor'])
                ->where('month', $month)->where('year', $year),
            'contractor'
        )
            ->when($request->filled('contractor_id'), fn($q) => $q->where('contractor_id', $request->contractor_id))
            ->when($request->filled('payment_status'), fn($q) => $q->where('payment_status', $request->payment_status))
            ->orderBy('contractor_id');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'contract-labour');
        }

        $records = $query->paginate(30)->withQueryString();

        $summaryQuery = BranchScope::scopeQueryVia(
            ContractWorkerPayroll::where('month', $month)->where('year', $year), 'contractor'
        )
            ->when($request->filled('contractor_id'), fn($q) => $q->where('contractor_id', $request->contractor_id))
            ->when($request->filled('payment_status'), fn($q) => $q->where('payment_status', $request->payment_status));

        $totals = $summaryQuery
            ->selectRaw('COUNT(DISTINCT contract_worker_id) as worker_count, SUM(total_wages) as total_wages, SUM(ot_amount) as total_ot, SUM(net_wages) as total_net')
            ->first();

        $paidCount = (clone $summaryQuery)->where('payment_status', 'paid')->count();

        $contractors = BranchScope::scopeQuery(Contractor::query())->orderBy('name')->get();

        return view('reports.contract-labour', compact('records', 'totals', 'paidCount', 'contractors', 'month', 'year'));
    }

    public function pfEsi(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $query = BranchScope::scopeQueryVia(
            PayrollRecord::with(['employee.department'])
                ->where('month', $month)->where('year', $year),
            'employee'
        )
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderBy('employee_id');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'pf-esi');
        }

        $records = $query->paginate(30)->withQueryString();

        $summaryQuery = BranchScope::scopeQueryVia(
            PayrollRecord::where('month', $month)->where('year', $year), 'employee'
        )
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)));

        $totals = $summaryQuery
            ->selectRaw('COUNT(*) as count, SUM(pf_employee) as pf_employee, SUM(pf_employer) as pf_employer, SUM(esi_employee) as esi_employee, SUM(esi_employer) as esi_employer')
            ->first();

        $departments = Department::orderBy('name')->get();

        return view('reports.pf-esi', compact('records', 'totals', 'departments', 'month', 'year'));
    }

    public function overtime(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        // Employee-wise OT summary for the month — hours/wages as actually paid on record.
        $summaryQuery = BranchScope::scopeQueryVia(
            PayrollRecord::with(['employee.department'])
                ->where('month', $month)->where('year', $year)
                ->where('ot_hours', '>', 0),
            'employee'
        )
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderByDesc('ot_hours');

        if ($request->filled('export')) {
            return $this->exportCsv($summaryQuery->get(), 'overtime');
        }

        $summary = $summaryQuery->paginate(15, ['*'], 'summary_page')->withQueryString();

        $totalsQuery = BranchScope::scopeQueryVia(
            PayrollRecord::where('month', $month)->where('year', $year)->where('ot_hours', '>', 0), 'employee'
        )
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)));

        $totals = $totalsQuery
            ->selectRaw('COUNT(*) as employee_count, SUM(ot_hours) as total_hours, SUM(ot_amount) as total_amount')
            ->first();

        // Date-wise OT punches for the same month, from daily attendance records.
        $dailyOt = BranchScope::scopeQueryVia(
            Attendance::with(['employee.department'])
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->where('ot_minutes', '>', 0),
            'employee'
        )
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderBy('date')
            ->paginate(15, ['*'], 'daily_page')
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('reports.overtime', compact('summary', 'totals', 'dailyOt', 'departments', 'month', 'year'));
    }

    public function lop(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $query = BranchScope::scopeQueryVia(
            PayrollRecord::with(['employee.department'])
                ->where('month', $month)->where('year', $year)
                ->where('lop_days', '>', 0),
            'employee'
        )
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderByDesc('lop_days');

        if ($request->filled('export')) {
            return $this->exportCsv($query->get(), 'lop');
        }

        $records = $query->paginate(30)->withQueryString();

        $summaryQuery = BranchScope::scopeQueryVia(
            PayrollRecord::where('month', $month)->where('year', $year)->where('lop_days', '>', 0), 'employee'
        )
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)));

        $totals = $summaryQuery
            ->selectRaw('COUNT(*) as employee_count, SUM(lop_days) as total_days, SUM(lop_deduction) as total_deduction')
            ->first();

        $departments = Department::orderBy('name')->get();

        return view('reports.lop', compact('records', 'totals', 'departments', 'month', 'year'));
    }

    private function exportCsv($records, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Fine-grained Branch Administration gate — CSV is the only export
        // format this app currently produces, so it's treated as the "Export
        // Excel" action. Additive: no-op for legacy/Super Admin accounts.
        if (BranchScope::isBranchScopedUser()) {
            $moduleKey = match ($type) {
                'attendance' => 'attendance',
                'employees' => 'employees',
                'leave' => 'leave',
                'payroll', 'pf-esi', 'overtime', 'lop' => 'payroll',
                'contract-labour' => 'contractors',
                default => 'reports',
            };

            if (! \App\Support\BranchAdminPermissions::can(auth()->user(), $moduleKey, 'export_excel')) {
                abort(403, 'You do not have the "Export Excel" permission for this report in Branch Administration.');
            }
        }

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
            'attendance'      => ['Date', 'Employee', 'Department', 'First In', 'Last Out', 'Work Hours', 'Status'],
            'employees'       => ['Code', 'Name', 'Department', 'Designation', 'Branch', 'Type', 'Joining Date', 'Status'],
            'leave'           => ['Employee', 'Department', 'Leave Type', 'From', 'To', 'Days', 'Status'],
            'payroll'         => ['Employee', 'Department', 'Basic', 'Gross', 'Deductions', 'Net Salary', 'Status'],
            'contract-labour' => ['Worker', 'Contractor', 'Present Days', 'Absent Days', 'OT Hours', 'Gross Wages', 'Deductions', 'Net Wages', 'Payment Status'],
            'pf-esi'          => ['Employee', 'Department', 'PF Employee', 'PF Employer', 'ESI Employee', 'ESI Employer'],
            'overtime'        => ['Employee', 'Department', 'OT Hours', 'OT Wages', 'Month', 'Year'],
            'lop'             => ['Employee', 'Department', 'LOP Days', 'LOP Deduction', 'Net Salary'],
            default           => [],
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
            'contract-labour' => [
                optional($record->worker)->name,
                optional($record->contractor)->name,
                $record->present_days,
                $record->absent_days,
                $record->ot_hours,
                $record->gross_wages,
                $record->deductions,
                $record->net_wages,
                $record->payment_status,
            ],
            'pf-esi' => [
                optional($record->employee)->full_name,
                optional(optional($record->employee)->department)->name,
                $record->pf_employee,
                $record->pf_employer,
                $record->esi_employee,
                $record->esi_employer,
            ],
            'overtime' => [
                optional($record->employee)->full_name,
                optional(optional($record->employee)->department)->name,
                $record->ot_hours,
                $record->ot_amount,
                $record->month,
                $record->year,
            ],
            'lop' => [
                optional($record->employee)->full_name,
                optional(optional($record->employee)->department)->name,
                $record->lop_days,
                $record->lop_deduction,
                $record->net_salary,
            ],
            default      => [],
        };
    }
}
