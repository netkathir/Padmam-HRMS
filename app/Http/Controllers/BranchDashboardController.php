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
 * Branch Dashboard (Dashboard FSD 5.3) — a single selected branch at a time,
 * distinct from the Overall Dashboard's multi-branch aggregate. Reuses
 * BranchScope::authorizedBranchIds() for the branch selector's option set
 * (auto-selected when exactly one), then every query below is scoped to
 * that one branch id, matching the BranchScope pattern used throughout the
 * rest of the app (a single "currently effective" branch).
 */
class BranchDashboardController extends Controller
{
    public function index(Request $request)
    {
        $authorizedBranchIds = BranchScope::authorizedBranchIds();

        if ($authorizedBranchIds->isEmpty()) {
            abort(403, 'You are not authorized for any branch.');
        }

        $requestedBranchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $branchId = ($requestedBranchId && $authorizedBranchIds->contains($requestedBranchId))
            ? $requestedBranchId
            : $authorizedBranchIds->first();

        // Keep the global Branch Switcher session in sync with this page's
        // own selector for a Super Admin — otherwise a KPI drill-down link
        // (routed through BranchScope::currentBranchId() elsewhere in the
        // app) could land on a different branch than what's shown here.
        if (auth()->user()->isSuperAdmin()) {
            session(['current_branch_id' => $branchId]);
        }

        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $date = $date->startOfDay();

        $filters = [
            'branch_id' => $branchId,
            'date' => $date,
            'employee_type' => in_array($request->input('employee_type'), ['staff', 'labour'], true) ? $request->input('employee_type') : null,
            'labour_type' => in_array($request->input('labour_type'), ['company_labour', 'contract_labour'], true) ? $request->input('labour_type') : null,
            'contractor_id' => $request->filled('contractor_id') ? (int) $request->contractor_id : null,
        ];

        $kpis = $this->computeKpis($filters);
        $charts = $this->computeCharts($filters);

        $authorizedBranches = Branch::whereIn('id', $authorizedBranchIds)->orderBy('name')->get();
        // Contractor is a global master (no branch dimension).
        $contractors = Contractor::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.branch', [
            'filters' => $filters,
            'kpis' => $kpis,
            'charts' => $charts,
            'authorizedBranches' => $authorizedBranches,
            'contractors' => $contractors,
        ]);
    }

    /**
     * Columns are qualified with `employees.` throughout — Department (and
     * other tables this query gets joined to, e.g. for the department-wise
     * chart) also has a branch_id column, so a bare `branch_id` becomes an
     * ambiguous-column SQL error once that join is added.
     */
    private function applyEmployeeFilters($query, array $filters)
    {
        $query->where('employees.branch_id', $filters['branch_id']);

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

    private function computeKpis(array $filters): array
    {
        $dateStr = $filters['date']->toDateString();

        $activeEmployees = $this->applyEmployeeFilters(Employee::active(), $filters)->count();

        $present = Attendance::where('date', $dateStr)
            ->whereIn('status', ['present', 'half_day', 'late', 'early_exit'])
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->count();

        $absent = Attendance::where('date', $dateStr)
            ->where('status', 'absent')
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->count();

        $onLeave = LeaveRequest::coveringDate($dateStr)
            ->where('status', 'approved')
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->distinct()
            ->count('employee_id');

        $lateEntry = Attendance::where('date', $dateStr)
            ->where('status', 'late')
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->count();

        $earlyExit = Attendance::where('date', $dateStr)
            ->where('status', 'early_exit')
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->count();

        $overtimeMinutes = Attendance::where('date', $dateStr)
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->sum('ot_minutes');

        $payrollQuery = fn () => PayrollRecord::where('month', $filters['date']->month)->where('year', $filters['date']->year)
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters));

        $payrollAmount = (float) ($payrollQuery()->sum('net_salary') ?? 0);
        $lopEmployees = $payrollQuery()->where('lop_days', '>', 0)->distinct()->count('employee_id');

        return [
            'active_employees' => $activeEmployees,
            'present' => $present,
            'absent' => $absent,
            'on_leave' => $onLeave,
            'lop' => $lopEmployees,
            'late_entry' => $lateEntry,
            'early_exit' => $earlyExit,
            'overtime_hours' => round($overtimeMinutes / 60, 1),
            'payroll_amount' => $payrollAmount,
        ];
    }

    private function computeCharts(array $filters): array
    {
        // Attendance Trend — trailing 30 days ending at the selected date.
        $rangeStart = $filters['date']->copy()->subDays(29);
        $attendanceRows = Attendance::whereBetween('date', [$rangeStart->toDateString(), $filters['date']->toDateString()])
            ->whereHas('employee', fn ($q) => $this->applyEmployeeFilters($q, $filters))
            ->selectRaw('date, status, COUNT(*) as cnt')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $days = collect();
        $cursor = $rangeStart->copy();
        while ($cursor->lte($filters['date'])) {
            $days->push($cursor->toDateString());
            $cursor->addDay();
        }

        $presentByDay = $attendanceRows->whereIn('status', ['present', 'half_day', 'late', 'early_exit'])
            ->groupBy('date')->map(fn ($rows) => $rows->sum('cnt'));
        $absentByDay = $attendanceRows->where('status', 'absent')->groupBy('date')->map(fn ($rows) => $rows->sum('cnt'));

        $attendanceTrend = [
            'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
            'present' => $days->map(fn ($d) => (int) ($presentByDay[$d] ?? 0))->all(),
            'absent' => $days->map(fn ($d) => (int) ($absentByDay[$d] ?? 0))->all(),
        ];

        // Department-wise Strength.
        $departmentWise = $this->applyEmployeeFilters(Employee::active(), $filters)
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as label, COUNT(*) as value')
            ->groupBy('departments.name')
            ->orderByDesc('value')
            ->get();

        // Contractor-wise Strength. Contractor is a global master (no branch
        // dimension), so this is organization-wide, optionally narrowed to
        // one contractor.
        $contractorWise = ContractWorker::active()
            ->join('contractors', 'contract_workers.contractor_id', '=', 'contractors.id')
            ->when($filters['contractor_id'], fn ($q) => $q->where('contractors.id', $filters['contractor_id']))
            ->selectRaw('contractors.name as label, COUNT(*) as value')
            ->groupBy('contractors.name')
            ->orderByDesc('value')
            ->get();

        return [
            'attendance_trend' => $attendanceTrend,
            'department_wise' => $departmentWise,
            'contractor_wise' => $contractorWise,
        ];
    }
}
