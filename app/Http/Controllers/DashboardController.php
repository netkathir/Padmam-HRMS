<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Contractor;
use App\Models\ContractWorker;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Overall Dashboard (Dashboard FSD 5.2). Distinct from BranchDashboardController
 * (5.3) — this one aggregates across every branch the user is authorized for
 * (BranchScope::authorizedBranchIds(), a multi-branch set), not the single
 * "currently effective" branch (BranchScope::currentBranchId()) used
 * everywhere else in the app.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);

        $kpis = $this->computeKpis($filters);
        $charts = $this->computeCharts($filters, $kpis);

        $authorizedBranches = Branch::whereIn('id', $filters['authorized_branch_ids'])->orderBy('name')->get();
        // Contractor is a global master (no branch dimension).
        $contractors = Contractor::where('is_active', true)
            ->orderBy('name')
            ->get();

        [$birthdays, $anniversaries] = $this->upcomingEvents($filters);
        $recentLeaves = LeaveRequest::whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->with(['employee:id,first_name,last_name', 'leaveType:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'filters' => $filters,
            'kpis' => $kpis,
            'charts' => $charts,
            'authorizedBranches' => $authorizedBranches,
            'contractors' => $contractors,
            'birthdays' => $birthdays,
            'anniversaries' => $anniversaries,
            'recentLeaves' => $recentLeaves,
        ]);
    }

    /** Unchanged from the previous dashboard, just re-scoped to the multi-branch filter set. */
    private function upcomingEvents(array $filters): array
    {
        $upcomingDayCodes = collect(range(0, 7))
            ->map(fn ($offset) => now()->copy()->addDays($offset)->format('m-d'))
            ->all();

        $upcomingEmployees = $this->baseEmployeeQuery($filters)
            ->select('id', 'first_name', 'last_name', 'date_of_birth', 'date_of_joining', 'department_id')
            ->with('department:id,name')
            ->get();

        $birthdays = $upcomingEmployees
            ->filter(fn ($e) => $e->date_of_birth && in_array($e->date_of_birth->format('m-d'), $upcomingDayCodes, true))
            ->take(5)
            ->values();

        $anniversaries = $upcomingEmployees
            ->filter(fn ($e) => $e->date_of_joining && in_array($e->date_of_joining->format('m-d'), $upcomingDayCodes, true))
            ->take(5)
            ->values();

        return [$birthdays, $anniversaries];
    }

    private function resolveFilters(Request $request): array
    {
        $authorizedBranchIds = BranchScope::authorizedBranchIds();

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : now();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo->copy(), $dateFrom->copy()];
        }

        // Never trust a submitted branch_ids[] beyond what this user is
        // actually authorized for — intersect, don't just accept. If every
        // submitted id was unauthorized (intersection empty), fall back to
        // the full authorized set rather than showing an empty dashboard —
        // same "no selection" behavior as not submitting branch_ids at all.
        $requestedBranchIds = collect($request->input('branch_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id);

        $branchIds = $requestedBranchIds->isNotEmpty()
            ? $requestedBranchIds->intersect($authorizedBranchIds)->values()
            : collect();

        if ($branchIds->isEmpty()) {
            $branchIds = $authorizedBranchIds->values();
        }

        return [
            'date_from' => $dateFrom->startOfDay(),
            'date_to' => $dateTo->startOfDay(),
            'branch_ids' => $branchIds,
            'authorized_branch_ids' => $authorizedBranchIds,
            'employee_type' => in_array($request->input('employee_type'), ['staff', 'labour'], true) ? $request->input('employee_type') : null,
            'labour_type' => in_array($request->input('labour_type'), ['company_labour', 'contract_labour'], true) ? $request->input('labour_type') : null,
            'contractor_id' => $request->filled('contractor_id') ? (int) $request->contractor_id : null,
        ];
    }

    /**
     * Employee Type / Labour Type / Contractor filters, applied consistently
     * everywhere an Employee (or a query reached via the employee relation)
     * is queried — always alongside the branch_ids multi-select. Columns are
     * qualified with `employees.` since some chart queries join in other
     * tables (e.g. departments) that also carry a branch_id column — a bare
     * `branch_id` would be an ambiguous-column SQL error in that context.
     */
    private function applyEmployeeFilters($query, array $filters)
    {
        $query->whereIn('employees.branch_id', $filters['branch_ids']);

        if ($filters['employee_type']) {
            $query->where('employees.primary_employee_type', $filters['employee_type']);
        }
        if ($filters['labour_type']) {
            $query->where('employees.labour_type', $filters['labour_type']);
        }
        if ($filters['contractor_id']) {
            $query->where('employees.contractor_id', $filters['contractor_id']);
        }

        return $query;
    }

    private function baseEmployeeQuery(array $filters)
    {
        return $this->applyEmployeeFilters(Employee::active(), $filters);
    }

    /** Every calendar month touched by the selected date range, e.g. [{month:7,year:2026}]. */
    private function monthsInRange(array $filters): array
    {
        $months = [];
        $cursor = $filters['date_from']->copy()->startOfMonth();
        $end = $filters['date_to']->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $months[] = ['month' => $cursor->month, 'year' => $cursor->year];
            $cursor->addMonth();
        }

        return $months;
    }

    private function payrollQueryForRange(array $filters)
    {
        $months = $this->monthsInRange($filters);

        return PayrollRecord::where(function ($q) use ($months) {
            foreach ($months as $m) {
                $q->orWhere(fn ($q2) => $q2->where('month', $m['month'])->where('year', $m['year']));
            }
        })->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters));
    }

    private function computeKpis(array $filters): array
    {
        if ($filters['branch_ids']->isEmpty()) {
            // No branch this user is authorized for — every KPI is
            // legitimately zero, no need to run any query.
            return array_fill_keys([
                'total_employees', 'staff_count', 'company_labour_count', 'contract_labour_count',
                'contractor_count', 'present_employees', 'absent_employees', 'on_leave_employees',
                'lop_employees', 'monthly_gross', 'monthly_net', 'employer_pf', 'employer_esi',
            ], 0);
        }

        $snapshotDate = $filters['date_to']->toDateString();

        $totalEmployees = $this->baseEmployeeQuery($filters)->count();
        $staffCount = $this->baseEmployeeQuery($filters)->where('primary_employee_type', 'staff')->count();
        $companyLabourCount = $this->baseEmployeeQuery($filters)
            ->where('primary_employee_type', 'labour')->where('labour_type', 'company_labour')->count();

        // Contractor is a global master (no branch dimension), so these two
        // KPIs are organization-wide, optionally narrowed to one contractor.
        $contractLabourCount = ContractWorker::active()
            ->when($filters['contractor_id'], function ($q) use ($filters) {
                $q->whereHas('contractor', fn ($q2) => $q2->where('id', $filters['contractor_id']));
            })->count();

        $contractorCount = Contractor::where('is_active', true)
            ->when($filters['contractor_id'], fn ($q) => $q->where('id', $filters['contractor_id']))
            ->count();

        $presentEmployees = Attendance::where('date', $snapshotDate)
            ->whereIn('status', ['present', 'half_day', 'late', 'early_exit'])
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->count();

        $absentEmployees = Attendance::where('date', $snapshotDate)
            ->where('status', 'absent')
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->count();

        $onLeaveEmployees = LeaveRequest::coveringDate($snapshotDate)
            ->where('status', 'approved')
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->distinct()
            ->count('employee_id');

        $payrollAgg = $this->payrollQueryForRange($filters)
            ->selectRaw('SUM(gross_earnings) as gross, SUM(net_salary) as net, SUM(pf_employer) as pf_employer, SUM(esi_employer) as esi_employer')
            ->first();

        $lopEmployees = $this->payrollQueryForRange($filters)
            ->where('lop_days', '>', 0)
            ->distinct()
            ->count('employee_id');

        return [
            'total_employees' => $totalEmployees,
            'staff_count' => $staffCount,
            'company_labour_count' => $companyLabourCount,
            'contract_labour_count' => $contractLabourCount,
            'contractor_count' => $contractorCount,
            'present_employees' => $presentEmployees,
            'absent_employees' => $absentEmployees,
            'on_leave_employees' => $onLeaveEmployees,
            'lop_employees' => $lopEmployees,
            'monthly_gross' => (float) ($payrollAgg->gross ?? 0),
            'monthly_net' => (float) ($payrollAgg->net ?? 0),
            'employer_pf' => (float) ($payrollAgg->pf_employer ?? 0),
            'employer_esi' => (float) ($payrollAgg->esi_employer ?? 0),
        ];
    }

    private function computeCharts(array $filters, array $kpis): array
    {
        if ($filters['branch_ids']->isEmpty()) {
            return [
                'branch_wise_employees' => collect(),
                'attendance_summary' => ['labels' => [], 'present' => [], 'absent' => [], 'on_leave' => []],
                'employee_type' => ['staff' => 0, 'company_labour' => 0, 'contract_labour' => 0],
                'payroll_summary' => collect(),
                'contractor_wise_labour' => collect(),
            ];
        }

        // Branch-wise Employee Chart — only meaningful when more than one
        // branch is in scope (otherwise it's a single bar).
        $branchWiseEmployees = $this->baseEmployeeQuery($filters)
            ->join('branches', 'employees.branch_id', '=', 'branches.id')
            ->selectRaw('branches.name as label, COUNT(*) as value')
            ->groupBy('branches.name')
            ->orderByDesc('value')
            ->get();

        // Attendance Summary Chart — day-by-day trend across the range.
        $attendanceRows = Attendance::whereBetween('date', [$filters['date_from']->toDateString(), $filters['date_to']->toDateString()])
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->selectRaw('date, status, COUNT(*) as cnt')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $days = collect();
        $cursor = $filters['date_from']->copy();
        while ($cursor->lte($filters['date_to'])) {
            $days->push($cursor->toDateString());
            $cursor->addDay();
        }

        $presentByDay = $attendanceRows->whereIn('status', ['present', 'half_day', 'late', 'early_exit'])
            ->groupBy('date')->map(fn ($rows) => $rows->sum('cnt'));
        $absentByDay = $attendanceRows->where('status', 'absent')->groupBy('date')->map(fn ($rows) => $rows->sum('cnt'));
        $onLeaveByDay = $attendanceRows->where('status', 'on_leave')->groupBy('date')->map(fn ($rows) => $rows->sum('cnt'));

        $attendanceSummary = [
            'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
            'present' => $days->map(fn ($d) => (int) ($presentByDay[$d] ?? 0))->all(),
            'absent' => $days->map(fn ($d) => (int) ($absentByDay[$d] ?? 0))->all(),
            'on_leave' => $days->map(fn ($d) => (int) ($onLeaveByDay[$d] ?? 0))->all(),
        ];

        // Employee Type Chart — reuses the same 3-way split as the KPIs.
        $employeeType = [
            'staff' => $kpis['staff_count'],
            'company_labour' => $kpis['company_labour_count'],
            'contract_labour' => $kpis['contract_labour_count'],
        ];

        // Payroll Summary Chart — trailing 6 calendar months ending at the
        // range's end month (a trend view, independent of the range width).
        $payrollSummary = collect(range(5, 0))->map(function ($monthsAgo) use ($filters) {
            $point = $filters['date_to']->copy()->subMonths($monthsAgo)->startOfMonth();
            $agg = PayrollRecord::where('month', $point->month)->where('year', $point->year)
                ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
                ->selectRaw('SUM(gross_earnings) as gross, SUM(net_salary) as net')
                ->first();

            return [
                'label' => $point->format('M Y'),
                'gross' => (float) ($agg->gross ?? 0),
                'net' => (float) ($agg->net ?? 0),
            ];
        })->values();

        // Contractor-wise Labour Chart. Contractor is a global master (no
        // branch dimension), so this is organization-wide, optionally
        // narrowed to one contractor.
        $contractorWiseLabour = ContractWorker::active()
            ->join('contractors', 'contract_workers.contractor_id', '=', 'contractors.id')
            ->when($filters['contractor_id'], fn ($q) => $q->where('contractors.id', $filters['contractor_id']))
            ->selectRaw('contractors.name as label, COUNT(*) as value')
            ->groupBy('contractors.name')
            ->orderByDesc('value')
            ->get();

        return [
            'branch_wise_employees' => $branchWiseEmployees,
            'attendance_summary' => $attendanceSummary,
            'employee_type' => $employeeType,
            'payroll_summary' => $payrollSummary,
            'contractor_wise_labour' => $contractorWiseLabour,
        ];
    }
}
