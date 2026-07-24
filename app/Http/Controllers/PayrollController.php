<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BiometricUpload;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\PayrollRecord;
use App\Models\PayrollPayment;
use App\Models\EmployeeSalaryStructure;
use App\Models\Attendance;
use App\Models\BusinessRule;
use App\Models\Contractor;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\SalarySlab;
use App\Models\Setting;
use App\Support\BranchAdminPermissions;
use App\Support\BranchScope;
use App\Support\RuleEngine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $records = BranchScope::scopeQueryVia(
            PayrollRecord::with(['employee.department'])
                ->where('month', $month)->where('year', $year),
            'employee'
        )
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $request->department_id)))
            ->orderBy('created_at', 'desc')
            ->paginate(25)->withQueryString();

        $summary = BranchScope::scopeQueryVia(
            PayrollRecord::where('month', $month)->where('year', $year), 'employee'
        )
            ->selectRaw('COUNT(*) as count, SUM(gross_earnings) as gross, SUM(net_salary) as net, SUM(total_deductions) as deductions')
            ->first();

        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        // Module 11 (FSD 15.2) — Confirm/Close/Reopen each have their own
        // permission flag now (previously all three collapsed onto
        // approve/process).
        $canConfirm = ! BranchScope::isBranchScopedUser() || BranchAdminPermissions::can(auth()->user(), 'payroll', 'confirm');
        $canClose = ! BranchScope::isBranchScopedUser() || BranchAdminPermissions::can(auth()->user(), 'payroll', 'close');
        $canReopen = ! BranchScope::isBranchScopedUser() || BranchAdminPermissions::can(auth()->user(), 'payroll', 'reopen');

        return view('payroll.index', compact('records', 'summary', 'month', 'year', 'departments', 'canConfirm', 'canClose', 'canReopen'));
    }

    public function generateForm(Request $request)
    {
        $departments = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();

        $recentPayrolls = BranchScope::scopeQueryVia(PayrollRecord::query(), 'employee')
            ->selectRaw(
                'month, year, status,
                 COUNT(DISTINCT employee_id) as employee_count,
                 SUM(gross_earnings) as total_gross,
                 SUM(net_salary)     as total_net'
            )
            ->groupBy('year', 'month', 'status')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        // FSD 13.2/13.4 — Payroll Preconditions, shown as informational
        // "System" status indicators (not hard gates — PF/ESI/TDS/earnings-
        // deduction rule availability is already guaranteed by the existing
        // Rule-Engine -> SalarySlab fallback chain, which never hard-fails).
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);
        $currentBranchId = BranchScope::currentBranchId();
        $branchId = $request->input('branch_id', $currentBranchId);
        $branches = $currentBranchId ? Branch::where('id', $currentBranchId)->get() : Branch::active()->orderBy('name')->get();
        $contractors = BranchScope::scopeQuery(Contractor::where('is_active', true))->orderBy('name')->get();

        $preconditions = $this->checkPreconditions($month, $year, $branchId);

        return view('payroll.generate', compact('departments', 'recentPayrolls', 'branches', 'currentBranchId', 'month', 'year', 'branchId', 'preconditions', 'contractors'));
    }

    /**
     * FSD 13.2 "Attendance Processed"/"LOP Reviewed" (System status fields)
     * + 13.4 preconditions. Attendance-processed is derived from
     * `BiometricUpload` OR the mere presence of any `Attendance` row for the
     * period (covers manual entry, per FSD 11.3's own "unless manual entry
     * is permitted"). Informational only — does not block generation.
     */
    private function checkPreconditions(int $month, int $year, ?int $branchId): array
    {
        $periodStart = \Carbon\Carbon::create($year, $month, 1)->toDateString();
        $periodEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $employeeIds = Employee::when($branchId, fn($q) => $q->where('branch_id', $branchId))->pluck('id');

        $biometricProcessed = $branchId
            ? BiometricUpload::where('branch_id', $branchId)
                ->where('period_from', '<=', $periodEnd)->where('period_to', '>=', $periodStart)
                ->where('status', 'completed')->exists()
            : false;
        $anyAttendance = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$periodStart, $periodEnd])->exists();

        $unresolvedAttendance = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->whereIn('status', ['missing_punch', 'pending_review'])->count();

        $pendingLeave = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'pending')
            ->where('start_date', '<=', $periodEnd)->where('end_date', '>=', $periodStart)
            ->count();

        $withoutSalary = Employee::whereIn('id', $employeeIds)->where('status', 'active')
            ->whereDoesntHave('currentSalary')->count();

        return [
            'attendance_processed' => $biometricProcessed || $anyAttendance,
            'unresolved_attendance_count' => $unresolvedAttendance,
            'pending_leave_count' => $pendingLeave,
            'employees_without_salary_count' => $withoutSalary,
        ];
    }

    public function generate(Request $request)
    {
        if (BranchScope::isBranchScopedUser() && ! \App\Support\BranchAdminPermissions::can(auth()->user(), 'payroll', 'process')) {
            abort(403, 'You do not have the "Process" permission for Payroll in Branch Administration.');
        }

        $request->validate([
            'month'       => ['required', 'integer', 'between:1,12'],
            'year'        => ['required', 'integer', 'min:2020'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $month = (int)$request->month;
        $year  = (int)$request->year;

        $query = BranchScope::scopeQuery(Employee::active()->with(['currentSalary', 'branch']));
        if ($request->filled('employee_id')) $query->where('id', $request->employee_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        // FSD 13.2 — Labour Type / Contractor header filters.
        if ($request->filled('labour_type')) $query->where('labour_type', $request->labour_type);
        if ($request->filled('contractor_id')) $query->where('contractor_id', $request->contractor_id);

        $employees  = $query->get();
        // The config actually effective for this payroll period (by
        // effective_from), not simply the most-recently-created row — a
        // retroactive/back-dated run must apply the rates that were in
        // force at the time, not whatever was configured most recently.
        $periodDate = \Carbon\Carbon::create($year, $month, 1)->toDateString();

        $generated = $skipped = $errors = 0;
        $noSlabEmployees = [];
        $noSalaryEmployees = [];
        $negativeNetEmployees = [];
        $blockNegativeNet = Setting::get('payroll', 'block_negative_net_salary', true);

        foreach ($employees as $employee) {
            if ($employee->branch && ! $employee->branch->is_active) { $skipped++; continue; }

            $salary = $employee->currentSalary;
            if (! $salary) {
                // FSD 13.6 — "Employees without salary structures shall be
                // listed as exceptions" (previously only counted, not named).
                $noSalaryEmployees[] = $employee->full_name;
                $errors++; continue;
            }

            // Skip if already generated
            if (PayrollRecord::where('employee_id', $employee->id)->where('month', $month)->where('year', $year)->exists()) {
                $skipped++; continue;
            }

            $primaryType = $employee->primary_employee_type;
            $labourType  = $employee->labour_type;
            $branchId    = $employee->branch_id;
            $contractorId = $employee->contractor_id;

            // The Salary Slab is assigned directly on the employee's own
            // salary record (Employee Slab's Designation & Salary section) —
            // no auto-detection by CTC range anymore.
            $slab = $salary->slab;

            if (! $slab) {
                $noSlabEmployees[] = $employee->full_name;
                $errors++;
                continue;
            }

            $appliedRules = [];

            // Module 4 FSD 8.5 — Weekly Off Rule drives which days count as
            // working days; falls back to Branch::weekly_off_days, then to
            // the original hardcoded Sat/Sun exclusion.
            $weeklyOffRule = BusinessRule::resolveForEmployee($employee, 'weekly_off', $branchId, $primaryType, $labourType, $contractorId, $periodDate);
            $weeklyOffDays = $weeklyOffRule?->weeklyOffRule?->weekly_off_days ?? $employee->branch?->weekly_off_days ?? null;
            if ($weeklyOffRule) $appliedRules['weekly_off'] = $weeklyOffRule->id;
            $workingDays = $this->getWorkingDays($month, $year, $weeklyOffDays);

            // FSD 13.6 — "Employees who joined or left during the month
            // shall be paid according to eligible days." Clips the working-
            // day count to the employee's actual eligible window this
            // period; a factor of 1.0 (no scaling) when neither applies,
            // so an employee with a full month's tenure produces byte-
            // identical figures to before this feature existed.
            $periodStart = \Carbon\Carbon::create($year, $month, 1);
            $periodEnd = $periodStart->copy()->endOfMonth();
            $eligibleFrom = ($employee->date_of_joining && $employee->date_of_joining->gt($periodStart)) ? $employee->date_of_joining->toDateString() : null;
            $lastWorkingDate = $employee->exitRecord?->last_working_date;
            $eligibleTo = ($lastWorkingDate && $lastWorkingDate->lt($periodEnd)) ? $lastWorkingDate->toDateString() : null;
            $isProRated = $eligibleFrom || $eligibleTo;
            $proRatedDays = $isProRated ? $this->getWorkingDays($month, $year, $weeklyOffDays, $eligibleFrom, $eligibleTo) : $workingDays;
            $proRationFactor = $workingDays > 0 ? min(1.0, $proRatedDays / $workingDays) : 1.0;

            // Module 4 FSD 8.6 / Module 8 FSD 12.1+12.4 — LOP Rule + leave-derived breakdown.
            // Uses $proRatedDays (not the full month's $workingDays) as the eligible-days
            // baseline and scopes the attendance query to the same window, so a mid-month
            // joiner/leaver's pre-joining/post-exit days are never mistaken for unrecorded
            // absences. For an employee with a full month's tenure, $proRatedDays ===
            // $workingDays and $eligibleFrom/$eligibleTo are both null, so behavior is
            // byte-identical to before pro-ration existed.
            $lopBreakdown = $this->calculateLop($employee, $month, $year, $proRatedDays, $branchId, $primaryType, $labourType, $contractorId, $periodDate, $eligibleFrom, $eligibleTo);
            $lopDays = $lopBreakdown['calculated_lop_days'];
            if ($lopBreakdown['rule_id']) $appliedRules['lop'] = $lopBreakdown['rule_id'];
            $perDaySal = $lopDays > 0 ? ($salary->ctc / 12) / $workingDays * $lopDays : 0;
            // The employee's mapped Salary Slab applies its own LOP % on top
            // of the standard per-day-rate deduction (100% = unchanged full
            // deduction; less than 100% softens it for that slab).
            $perDaySal = $slab->lopDeduction($perDaySal);

            $gross = ($salary->basic_salary + $salary->hra + $salary->da
                   + $salary->ta + $salary->medical_allowance + $salary->special_allowance) * $proRationFactor;
            $net   = $gross - $perDaySal;

            // Module 4 FSD 8.7/8.8/8.9 — PF/ESI/TDS Rules take precedence
            // over the Module-3 Salary Slab percentages. A deployment with
            // no Rule Engine PF/ESI/TDS rules configured falls back entirely
            // to the Salary Slab's own percentages.
            $pfRule  = BusinessRule::resolveForEmployee($employee, 'pf', $branchId, $primaryType, $labourType, $contractorId, $periodDate);
            $esiRule = BusinessRule::resolveForEmployee($employee, 'esi', $branchId, $primaryType, $labourType, $contractorId, $periodDate);
            $tdsRule = BusinessRule::resolveForEmployee($employee, 'tds', $branchId, $primaryType, $labourType, $contractorId, $periodDate);
            $pfDetail  = $pfRule?->pfRule;
            $esiDetail = $esiRule?->esiRule;
            $tdsDetail = $tdsRule?->tdsRule;
            if ($pfRule) $appliedRules['pf'] = $pfRule->id;
            if ($esiRule) $appliedRules['esi'] = $esiRule->id;
            if ($tdsRule) $appliedRules['tds'] = $tdsRule->id;

            $pfEmp = $pfEmpEr = $esiEmp = $esiEmpEr = $tds = 0;
            $pfApplicable  = $pfDetail ? $pfDetail->pf_applicable : true;
            $esiApplicable = $esiDetail ? $esiDetail->esi_applicable : true;
            $tdsApplicable = $tdsDetail ? $tdsDetail->tds_applicable : true;

            // Null-safe (?->) throughout: $slab may legitimately be null
            // (employee type with zero configured slabs), and $pfDetail/
            // $esiDetail/$tdsDetail are null whenever no Rule Engine rule
            // resolves — both are the common case for a deployment that
            // hasn't configured that layer. This previously used plain `->`
            // (silent PHP warning today, not a crash, but still incorrect);
            // behavior is unchanged for every case that already worked.
            $pfEmployeePct  = $pfDetail?->employee_pf_percentage  ?? $slab?->pf_employee_percentage  ?? null;
            $pfEmployerPct  = $pfDetail?->employer_pf_percentage  ?? $slab?->pf_employer_percentage  ?? null;
            $esiEmployeePct = $esiDetail?->employee_esi_percentage ?? $slab?->esi_employee_percentage ?? null;
            $esiEmployerPct = $esiDetail?->employer_esi_percentage ?? $slab?->esi_employer_percentage ?? null;
            $pfWageCeiling  = $pfDetail?->pf_wage_ceiling ?? null;
            $esiWageCeiling = $esiDetail?->salary_slab_to ?? 21000;

            if ($employee->is_pf_applicable && $pfApplicable && $pfEmployeePct !== null) {
                $wageBase = ($pfDetail?->restrict_to_wage_ceiling ?? true) && $pfWageCeiling
                    ? min($salary->basic_salary, $pfWageCeiling)
                    : $salary->basic_salary;
                $pfEmp   = RuleEngine::roundAmount($wageBase * ($pfEmployeePct / 100), $pfDetail->rounding_method ?? null);
                $pfEmpEr = RuleEngine::roundAmount($wageBase * ($pfEmployerPct / 100), $pfDetail->rounding_method ?? null);
            }
            if ($employee->is_esi_applicable && $esiApplicable && $esiEmployeePct !== null && $gross <= $esiWageCeiling) {
                $esiEmp   = RuleEngine::roundAmount($gross * ($esiEmployeePct / 100), $esiDetail->rounding_method ?? null);
                $esiEmpEr = RuleEngine::roundAmount($gross * ($esiEmployerPct / 100), $esiDetail->rounding_method ?? null);
            }
            $tdsPct = $tdsDetail?->tds_percentage ?? $slab?->tds_percentage ?? null;
            if ($employee->is_tds_applicable && $tdsApplicable && $tdsPct) {
                $tds = RuleEngine::roundAmount($gross * ($tdsPct / 100), $tdsDetail->rounding_method ?? null);
            }

            // Module 4 FSD 8.10 — Overtime Rule. Attendance already computes
            // ot_minutes per day (see AttendanceController::recalculate());
            // this sums the period and applies the rule's rate/approval
            // policy. No rule configured, or attendance never populated
            // ot_minutes (no Attendance Rule active) — ot_amount stays 0
            // exactly as before this feature existed.
            [$otHours, $otAmount, $otRuleId] = $this->calculateOvertime($employee, $salary, $month, $year, $workingDays, $branchId, $primaryType, $labourType, $contractorId, $periodDate);
            if ($otRuleId) $appliedRules['overtime'] = $otRuleId;

            $totalDeductions = $pfEmp + $esiEmp + $tds;
            $netSalary       = $net - $totalDeductions + $otAmount;
            // FSD 13.4 "Total Employer Cost: Gross plus employer
            // contributions" — stored at generation time so it's preserved
            // historically (FSD 13.6) rather than re-derived later from
            // potentially-changed related data.
            $employerCost    = $gross + $pfEmpEr + $esiEmpEr;

            // FSD 13.6 — "Negative net salary shall be blocked or flagged
            // according to configuration."
            if ($netSalary < 0 && $blockNegativeNet) {
                $negativeNetEmployees[] = $employee->full_name;
                $errors++;
                continue;
            }
            if ($netSalary < 0) {
                $negativeNetEmployees[] = $employee->full_name;
            }

            $presentDays = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $month)->whereYear('date', $year)
                ->where('status', 'present')->count();

            DB::transaction(function () use (
                $employee, $salary, $month, $year, $gross, $net, $netSalary, $workingDays, $proRatedDays, $proRationFactor, $presentDays, $employerCost,
                $lopDays, $lopBreakdown, $pfEmp, $pfEmpEr, $esiEmp, $esiEmpEr, $tds, $totalDeductions, $otHours, $otAmount, $appliedRules
            ) {
                PayrollRecord::create([
                    'employee_id'      => $employee->id,
                    'month'            => $month,
                    'year'             => $year,
                    'working_days'     => $workingDays,
                    'pro_rated_days'   => round($proRatedDays, 2),
                    'present_days'     => $presentDays,
                    'absent_days'      => round($lopBreakdown['absent_days'], 2),
                    'lop_days'         => round($lopDays, 2),
                    'calculated_lop_days' => round($lopDays, 2),
                    'unpaid_leave_days'   => round($lopBreakdown['unpaid_leave_days'], 2),
                    'half_day_lop_days'   => round($lopBreakdown['half_day_lop_days'], 2),
                    'late_early_lop_days' => round($lopBreakdown['late_early_lop_days'], 2),
                    'lop_applied'      => true,
                    'ot_hours'         => round($otHours, 2),
                    // Stored pro-rated (not the raw structure values) so that gross_earnings
                    // always equals the sum of these component fields — otherwise any later
                    // recalculation from components (e.g. a manual adjustment) would silently
                    // discard the pro-ration. A factor of 1.0 for a full-month employee means
                    // these are byte-identical to $salary->basic_salary etc. as before.
                    'basic_salary'     => round($salary->basic_salary * $proRationFactor, 2),
                    'hra'              => round($salary->hra * $proRationFactor, 2),
                    'da'               => round($salary->da * $proRationFactor, 2),
                    'ta'               => round($salary->ta * $proRationFactor, 2),
                    'medical_allowance'=> round($salary->medical_allowance * $proRationFactor, 2),
                    'special_allowance'=> round($salary->special_allowance * $proRationFactor, 2),
                    'ot_amount'        => round($otAmount, 2),
                    'other_earnings'   => 0,
                    'gross_earnings'   => round($gross, 2),
                    'pf_employee'      => round($pfEmp, 2),
                    'pf_employer'      => round($pfEmpEr, 2),
                    'esi_employee'     => round($esiEmp, 2),
                    'esi_employer'     => round($esiEmpEr, 2),
                    'employer_cost'    => round($employerCost, 2),
                    'tds'              => round($tds, 2),
                    'advance_deduction'=> 0,
                    'lop_deduction'    => round($gross - $net, 2),
                    'other_deductions' => 0,
                    'total_deductions' => round($totalDeductions, 2),
                    'net_salary'       => round($netSalary, 2),
                    'status'           => 'calculated',
                    'generated_by'     => auth()->id(),
                    'generated_at'     => now(),
                    'applied_rules'    => $appliedRules ?: null,
                ]);
            });

            $generated++;
        }

        $message = "Payroll processed: {$generated} generated, {$skipped} skipped, {$errors} errors.";
        if ($noSlabEmployees) {
            $message .= ' No applicable salary slab found for: ' . implode(', ', $noSlabEmployees) . '.';
        }
        if ($noSalaryEmployees) {
            $message .= ' No salary structure for: ' . implode(', ', $noSalaryEmployees) . '.';
        }
        if ($negativeNetEmployees) {
            $message .= ($blockNegativeNet ? ' Negative net salary blocked for: ' : ' Negative net salary flagged for: ') . implode(', ', $negativeNetEmployees) . '.';
        }

        $hasExceptions = $noSlabEmployees || $noSalaryEmployees;
        return redirect()->route('payroll.index', ['month' => $month, 'year' => $year])
            ->with($hasExceptions ? 'error' : 'success', $message);
    }

    /**
     * Module 4 FSD 8.6 / Module 8 FSD 12.1+12.4 — LOP calculation, now a full
     * breakdown (Absent Days / Unpaid Leave Days / Half-Day LOP / Late-Early
     * Exit LOP / Calculated LOP Days) instead of a single total, so the LOP
     * Review screen can display each FSD-listed component. Falls back to the
     * original `workingDays - (present+half_day count)` when no LOP Rule is
     * configured, preserving today's behavior exactly for any deployment
     * that hasn't configured one.
     */
    private function calculateLop(Employee $employee, int $month, int $year, int $workingDays, ?int $branchId, ?string $primaryType, ?string $labourType, ?int $contractorId, string $periodDate, ?string $fromDate = null, ?string $toDate = null): array
    {
        $rule = BusinessRule::resolveForEmployee($employee, 'lop', $branchId, $primaryType, $labourType, $contractorId, $periodDate);
        $detail = $rule?->lopRule;

        // $fromDate/$toDate (FSD 13.6 pro-ration) clip the attendance window to the
        // employee's eligible days this period; both null for every pre-existing
        // call pattern, so the query is unchanged for a full-month employee.
        $attendanceQuery = fn() => Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate));

        if (! $detail) {
            $presentDays = $attendanceQuery()->whereIn('status', ['present', 'half_day'])->count();
            $absentDays  = max(0, $workingDays - $presentDays);
            return [
                'absent_days'         => $absentDays,
                'unpaid_leave_days'   => 0.0,
                'half_day_lop_days'   => 0.0,
                'late_early_lop_days' => 0.0,
                'calculated_lop_days' => (float) $absentDays,
                'rule_id'             => null,
            ];
        }

        $presentCount   = $attendanceQuery()->where('status', 'present')->count();
        $halfDayCount   = $attendanceQuery()->where('status', 'half_day')->count();
        $explicitAbsent = $attendanceQuery()->where('status', 'absent')->count();
        // Working days with no attendance record at all (never marked, or a
        // missing punch that was never resolved to an explicit status) —
        // the old fallback treated every such day as LOP; "Missing Punch as
        // LOP" is this rule's configurable equivalent of that same gap.
        $unrecordedDays = max(0, $workingDays - $presentCount - $halfDayCount - $explicitAbsent);
        $absentDays     = $explicitAbsent + $unrecordedDays;

        $halfDayLop = $halfDayCount * (float) $detail->half_day_lop_value;

        $lopDays = $halfDayLop;
        if ($detail->absent_day_as_lop) {
            $lopDays += $explicitAbsent * (float) $detail->full_day_lop_value;
        }
        if ($detail->missing_punch_as_lop) {
            $lopDays += $unrecordedDays * (float) $detail->full_day_lop_value;
        }

        $lateEarlyLop = 0.0;
        if ($detail->late_count_conversion) {
            $lateCount = $attendanceQuery()->where('is_late', true)->count();
            $lateEarlyLop += intdiv($lateCount, $detail->late_count_conversion) * (float) $detail->half_day_lop_value;
        }
        if ($detail->early_exit_conversion) {
            $earlyCount = $attendanceQuery()->where('is_early_exit', true)->count();
            $lateEarlyLop += intdiv($earlyCount, $detail->early_exit_conversion) * (float) $detail->half_day_lop_value;
        }
        $lopDays += $lateEarlyLop;

        // FSD 12.1 — "Converted to LOP when unpaid or when paid leave
        // balance is insufficient." Wires up the previously-dead
        // `unpaid_leave_as_lop` flag; no rule / flag off means this is
        // always 0, identical to every deployment before this feature.
        $unpaidLeaveDays = $detail->unpaid_leave_as_lop
            ? $this->calculateUnpaidLeaveDays($employee, $month, $year)
            : 0.0;
        $lopDays += $unpaidLeaveDays;

        return [
            'absent_days'         => (float) $absentDays,
            'unpaid_leave_days'   => $unpaidLeaveDays,
            'half_day_lop_days'   => $halfDayLop,
            'late_early_lop_days' => $lateEarlyLop,
            'calculated_lop_days' => max(0, $lopDays),
            'rule_id'             => $rule->id,
        ];
    }

    /**
     * FSD 12.1 — unpaid-leave-type approved requests convert fully to LOP;
     * approved paid-leave requests convert only the portion that pushed the
     * employee's balance negative (only relevant when
     * Setting::leave.allow_negative_balance is enabled, since store()
     * otherwise blocks a paid request from exceeding balance in the first
     * place).
     */
    private function calculateUnpaidLeaveDays(Employee $employee, int $month, int $year): float
    {
        $unpaidDays = (float) LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereMonth('start_date', $month)->whereYear('start_date', $year)
            ->whereHas('leaveType', fn($q) => $q->where('is_paid', false))
            ->sum('total_days');

        if (! Setting::get('leave', 'allow_negative_balance', false)) {
            return $unpaidDays;
        }

        $paidLeaveTypeIds = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereMonth('start_date', $month)->whereYear('start_date', $year)
            ->whereHas('leaveType', fn($q) => $q->where('is_paid', true))
            ->pluck('leave_type_id')->unique();

        foreach ($paidLeaveTypeIds as $leaveTypeId) {
            $balance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveTypeId)->where('year', $year)->first();
            if ($balance && $balance->balance_days < 0) {
                $usedThisMonth = (float) LeaveRequest::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveTypeId)->where('status', 'approved')
                    ->whereMonth('start_date', $month)->whereYear('start_date', $year)
                    ->sum('total_days');
                $unpaidDays += min(abs((float) $balance->balance_days), $usedThisMonth);
            }
        }

        return $unpaidDays;
    }

    /**
     * Module 4 FSD 8.10 — Overtime Rule. Returns [otHours, otAmount, ruleId|null].
     */
    private function calculateOvertime(Employee $employee, EmployeeSalaryStructure $salary, int $month, int $year, int $workingDays, ?int $branchId, ?string $primaryType, ?string $labourType, ?int $contractorId, string $periodDate): array
    {
        $rule = BusinessRule::resolveForEmployee($employee, 'overtime', $branchId, $primaryType, $labourType, $contractorId, $periodDate);
        $detail = $rule?->overtimeRule;

        if (! $detail || ! $detail->overtime_applicable) {
            return [0, 0, null];
        }

        $query = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)->whereYear('date', $year);
        if ($detail->approval_required) {
            $query->where('approval_status', 'approved');
        }
        $otMinutes = (int) $query->sum('ot_minutes');

        if ($detail->minimum_overtime_minutes && $otMinutes < $detail->minimum_overtime_minutes) {
            return [0, 0, $rule->id];
        }
        if ($detail->maximum_overtime_per_day_minutes) {
            $days = max(1, $workingDays);
            $otMinutes = min($otMinutes, $detail->maximum_overtime_per_day_minutes * $days);
        }

        $otHours = $otMinutes / 60;
        $hourlyWage = $workingDays > 0 ? ($salary->basic_salary / $workingDays) / 8 : 0;
        $rate = (float) ($detail->overtime_rate ?? 0);

        $otAmount = match ($detail->overtime_calculation) {
            'fixed_rate' => $otHours * $rate,
            default => $otHours * $hourlyWage * ($rate ?: 1),
        };

        return [$otHours, $otAmount, $rule->id];
    }

    public function payslip(PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);
        $this->assertPayslipAllowed($payroll);
        $payroll->load(['employee.branch', 'employee.department', 'employee.designation', 'employee.employeeType', 'employee.contractor', 'employee.bankDetails', 'allowances', 'deductions', 'payments']);
        $company = \App\Models\CompanyProfile::first();
        return view('payroll.payslip', compact('payroll', 'company'));
    }

    /** FSD 13.7 — "Payslip shall be generated only after payroll confirmation or closure, according to configuration." */
    private function assertPayslipAllowed(PayrollRecord $payroll): void
    {
        if (Setting::get('payroll', 'payslip_requires_confirmation', true) && ! in_array($payroll->status, ['confirmed', 'closed', 'paid'], true)) {
            abort(403, 'This payslip is not available until payroll is confirmed or closed for this period.');
        }
    }

    public function payslipPdf(PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);
        $this->assertPayslipAllowed($payroll);
        $payroll->load(['employee.branch', 'employee.department', 'employee.designation', 'employee.employeeType', 'employee.contractor', 'employee.bankDetails']);
        $company = \App\Models\CompanyProfile::first();

        $pdf = Pdf::loadView('payroll.payslip-pdf', compact('payroll', 'company'))->setPaper('a4', 'portrait');
        $fileName = 'payslip-' . $payroll->employee->employee_code . '-' . $payroll->month . '-' . $payroll->year . '.pdf';
        return $pdf->download($fileName);
    }

    /** FSD 13.7 — "Bulk download." Zips one PDF per employee for the filtered period, using the same view every single download uses. */
    public function payslipBulk(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year'  => ['required', 'integer', 'min:2020'],
        ]);

        $query = PayrollRecord::with(['employee.branch', 'employee.department', 'employee.designation', 'employee.employeeType', 'employee.contractor', 'employee.bankDetails'])
            ->where('month', $data['month'])->where('year', $data['year']);
        $query = BranchScope::scopeQueryVia($query, 'employee');
        if (Setting::get('payroll', 'payslip_requires_confirmation', true)) {
            $query->whereIn('status', ['confirmed', 'closed', 'paid']);
        }
        $records = $query->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'No eligible payslips found for this period.');
        }

        $company = \App\Models\CompanyProfile::first();
        $zipPath = storage_path('app/temp/payslips-' . $data['month'] . '-' . $data['year'] . '-' . uniqid() . '.zip');
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        foreach ($records as $payroll) {
            $pdfContent = Pdf::loadView('payroll.payslip-pdf', compact('payroll', 'company'))->setPaper('a4', 'portrait')->output();
            $zip->addFromString('payslip-' . $payroll->employee->employee_code . '.pdf', $pdfContent);
        }
        $zip->close();

        return response()->download($zipPath, 'payslips-' . $data['month'] . '-' . $data['year'] . '.zip')->deleteFileAfterSend(true);
    }

    /** FSD 13.7 — "Email, when enabled." */
    public function emailPayslip(PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);
        $this->assertPayslipAllowed($payroll);

        if (! Setting::get('payroll', 'payslip_email_enabled', false)) {
            return back()->with('error', 'Payslip email is not enabled.');
        }

        $email = $payroll->employee->official_email ?? $payroll->employee->personal_email ?? null;
        if (! $email) {
            return back()->with('error', 'This employee has no email address on file.');
        }

        $payroll->load(['employee.branch', 'employee.department', 'employee.designation', 'employee.employeeType', 'employee.contractor', 'employee.bankDetails']);
        $company = \App\Models\CompanyProfile::first();
        $pdfContent = Pdf::loadView('payroll.payslip-pdf', compact('payroll', 'company'))->setPaper('a4', 'portrait')->output();
        $fileName = 'payslip-' . $payroll->employee->employee_code . '-' . $payroll->month . '-' . $payroll->year . '.pdf';

        \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\PayslipMail($payroll, $pdfContent, $fileName));

        return back()->with('success', 'Payslip emailed to ' . $email . '.');
    }

    public function paymentForm(PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);
        $payroll->load('employee', 'payments');
        return view('payroll.payment', compact('payroll'));
    }

    public function storePayment(Request $request, PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);

        // FSD 12.4 — payroll processing (recording payment) is blocked until
        // LOP has been explicitly confirmed for this employee's period on
        // the LOP Review screen.
        if ($payroll->calculated_lop_days > 0 && ! $payroll->lop_confirmed_at) {
            return redirect()->route('payroll.lop-review', ['month' => $payroll->month, 'year' => $payroll->year])
                ->with('error', 'LOP must be confirmed on the LOP Review screen before payment can be recorded for ' . $payroll->employee->full_name . '.');
        }

        // FSD 13.7 — "Payslip/payment shall be generated only after payroll
        // confirmation or closure." Payment specifically requires the
        // payroll to be Closed (the terminal pre-payment state in FSD
        // 13.6's lifecycle).
        if ($payroll->status !== 'closed') {
            return redirect()->route('payroll.index', ['month' => $payroll->month, 'year' => $payroll->year])
                ->with('error', 'This payroll must be Confirmed and Closed before payment can be recorded for ' . $payroll->employee->full_name . '.');
        }

        $data = $request->validate([
            'payment_date'     => ['required', 'date'],
            'payment_mode'     => ['required', 'in:bank_transfer,cash,cheque,upi'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'remarks'          => ['nullable', 'string'],
        ]);

        $data['payroll_id']   = $payroll->id;
        $data['processed_by'] = auth()->id();

        PayrollPayment::create($data);
        $payroll->update(['status' => 'paid']);

        return redirect()->route('payroll.payslip', $payroll)->with('success', 'Payment recorded.');
    }

    /**
     * FSD 13.6 "Edit permitted earnings or deductions" — a manual line item,
     * gated the same way Module 7's attendance correction gates overtime
     * hours (`BranchAdminPermissions::can(...,'payroll','approve')`).
     * FSD 13.6 "Every manual earning or deduction adjustment shall require a
     * reason" — reuses the existing `remarks` column on
     * `payroll_allowances`/`payroll_deductions`, enforced here as required.
     */
    public function manualAdjustment(Request $request, PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);

        // Module 11 (FSD 15.2) — dedicated Modify Payroll permission, not
        // the general Approve flag (manual earnings/deductions are a
        // materially different action from confirming/closing a payroll run).
        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'payroll', 'modify_payroll')) {
            abort(403, 'You do not have the "Modify Payroll" permission for Payroll in Branch Administration.');
        }

        if (! $payroll->isEditable()) {
            return back()->withErrors(['type' => 'This payroll record is ' . $payroll->status_label . ' and can no longer be edited. Reopen it first.']);
        }

        $data = $request->validate([
            'type'   => ['required', 'in:earning,deduction'],
            'name'   => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks'=> ['required', 'string', 'max:255'],
        ]);

        if ($data['type'] === 'earning') {
            PayrollAllowance::create(['payroll_id' => $payroll->id, 'name' => $data['name'], 'amount' => $data['amount'], 'remarks' => $data['remarks']]);
        } else {
            PayrollDeduction::create(['payroll_id' => $payroll->id, 'name' => $data['name'], 'amount' => $data['amount'], 'remarks' => $data['remarks']]);
        }

        $this->recalculateManualAdjustmentTotals($payroll);

        return back()->with('success', ucfirst($data['type']) . ' added.');
    }

    public function deleteManualAdjustment(Request $request, PayrollRecord $payroll, string $type, int $id)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);

        // Module 11 (FSD 15.2) — "Delete permission shall be restricted."
        // Requires BOTH Modify Payroll (it's a payroll change) AND the
        // dedicated Delete flag (it's a destructive action).
        if (BranchScope::isBranchScopedUser() && (
            ! BranchAdminPermissions::can(auth()->user(), 'payroll', 'modify_payroll')
            || ! BranchAdminPermissions::can(auth()->user(), 'payroll', 'delete')
        )) {
            abort(403, 'You do not have the "Modify Payroll" + "Delete" permissions for Payroll in Branch Administration.');
        }
        if (! $payroll->isEditable()) {
            return back()->withErrors(['type' => 'This payroll record is ' . $payroll->status_label . ' and can no longer be edited. Reopen it first.']);
        }

        if ($type === 'earning') {
            PayrollAllowance::where('payroll_id', $payroll->id)->where('id', $id)->delete();
        } else {
            PayrollDeduction::where('payroll_id', $payroll->id)->where('id', $id)->delete();
        }

        $this->recalculateManualAdjustmentTotals($payroll);

        return back()->with('success', 'Adjustment removed.');
    }

    /** Rolls the sum of manual allowance/deduction lines into other_earnings/other_deductions and recomputes gross/net/employer_cost. */
    private function recalculateManualAdjustmentTotals(PayrollRecord $payroll): void
    {
        $otherEarnings   = (float) $payroll->allowances()->sum('amount');
        $otherDeductions = (float) $payroll->deductions()->sum('amount');

        // Mirrors generate()'s exact formula: basic/hra/da/ta/medical/special are already
        // pro-rated at storage time (see PayrollRecord::create() above), ot_amount is added
        // after deductions rather than folded into gross, and other_earnings/other_deductions
        // are the only new inputs a manual adjustment introduces.
        $gross = (float) $payroll->basic_salary + $payroll->hra + $payroll->da + $payroll->ta
            + $payroll->medical_allowance + $payroll->special_allowance + $otherEarnings;
        $totalDeductions = (float) $payroll->pf_employee + $payroll->esi_employee + $payroll->tds
            + $payroll->advance_deduction + $payroll->lop_deduction + $otherDeductions;
        $netSalary = $gross - $totalDeductions + $payroll->ot_amount;
        $employerCost = $gross + $payroll->pf_employer + $payroll->esi_employer;

        $payroll->update([
            'other_earnings'   => round($otherEarnings, 2),
            'other_deductions' => round($otherDeductions, 2),
            'gross_earnings'   => round($gross, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_salary'       => round($netSalary, 2),
            'employer_cost'    => round($employerCost, 2),
        ]);
    }

    /**
     * Module 4 FSD 8.5 — Weekly Off Rule drives which days are excluded from
     * "working days" (falls back to the branch's own weekly_off_days, then
     * to the original hardcoded Sat/Sun exclusion when neither is configured).
     * FSD 13.6 pro-ration — $fromDate/$toDate optionally clip the counted
     * range to an employee's eligible window within the month (join/exit
     * mid-month); omitted (the default, and every pre-existing call site),
     * this behaves byte-for-byte as before — the full calendar month.
     */
    private function getWorkingDays(int $month, int $year, ?array $weeklyOffDays = null, ?string $fromDate = null, ?string $toDate = null): int
    {
        $dayNameToIndex = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $offIndexes = $weeklyOffDays
            ? array_values(array_filter(array_map(fn($d) => $dayNameToIndex[$d] ?? null, $weeklyOffDays), fn($v) => $v !== null))
            : [0, 6];

        $monthStart = \Carbon\Carbon::create($year, $month, 1);
        $monthEnd   = $monthStart->copy()->endOfMonth();

        $date = $fromDate ? \Carbon\Carbon::parse($fromDate)->max($monthStart) : $monthStart;
        $end  = $toDate ? \Carbon\Carbon::parse($toDate)->min($monthEnd) : $monthEnd;

        $days = 0;
        while ($date->lte($end)) {
            if (! in_array($date->dayOfWeek, $offIndexes, true)) $days++;
            $date->addDay();
        }

        return $days;
    }

    /**
     * Module 4 FSD 8.6 / Module 8 FSD 12.4 — "LOP Review screen shall
     * display LOP calculated from attendance and leave before payroll
     * processing." Lists a batch's just-generated draft payrolls for
     * review/adjustment, with the FSD's Branch/Employee Type/Labour
     * Type/Contractor filters.
     */
    public function lopReview(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $query = PayrollRecord::with(['employee.department', 'employee.employeeType', 'employee.contractor'])
            ->where('month', $month)->where('year', $year)->whereIn('status', PayrollRecord::EDITABLE_STATUSES);
        $query = BranchScope::scopeQueryVia($query, 'employee');

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('labour_type')) {
            $query->whereHas('employee', fn($q) => $q->where('labour_type', $request->labour_type));
        }
        if ($request->filled('contractor_id')) {
            $query->whereHas('employee', fn($q) => $q->where('contractor_id', $request->contractor_id));
        }

        $records = $query->orderBy('employee_id')->paginate(30)->withQueryString();

        $currentBranchId = BranchScope::currentBranchId();
        $branches      = $currentBranchId ? Branch::where('id', $currentBranchId)->get() : Branch::active()->orderBy('name')->get();
        $contractors   = BranchScope::scopeQuery(Contractor::where('is_active', true))->orderBy('name')->get();

        // FSD 13.3 — LOP Confirmation Prompt summary counts, computed over
        // the SAME filtered set the review screen (and the bulk "Confirm
        // LOP" action) already operates on.
        $allForPeriod = (clone $query)->get();
        $lopSummary = [
            'total_selected'        => $allForPeriod->count(),
            'calculated_lop_count'  => $allForPeriod->where('calculated_lop_days', '>', 0)->count(),
            'lop_applied_count'     => $allForPeriod->where('lop_applied', true)->where('lop_days', '>', 0)->count(),
            'lop_excluded_count'    => $allForPeriod->where('lop_applied', false)->count(),
            'manual_adjustment_count' => $allForPeriod->filter(fn($r) => abs((float) $r->lop_days - (float) $r->calculated_lop_days) >= 0.001)->count(),
            'unresolved_attendance_count' => Attendance::whereIn('employee_id', $allForPeriod->pluck('employee_id'))
                ->whereMonth('date', $month)->whereYear('date', $year)
                ->whereIn('status', ['missing_punch', 'pending_review'])->count(),
        ];

        return view('payroll.lop-review', compact('records', 'month', 'year', 'branches', 'contractors', 'currentBranchId', 'lopSummary'));
    }

    public function updateLop(Request $request, PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);
        $data = $request->validate([
            'lop_days' => ['required', 'numeric', 'min:0'],
            'lop_applied' => ['boolean'],
            'lop_override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->applyLopUpdate($payroll, (float) $data['lop_days'], $request->boolean('lop_applied'), $data['lop_override_reason'] ?? null);
        if ($result === 'reason_required') {
            return back()->withErrors(['lop_override_reason' => 'A reason is required when the approved LOP differs from the calculated LOP.'])->withInput();
        }

        return back()->with('success', $result === 'no_change' ? 'No change.' : ('LOP updated for ' . $payroll->employee->full_name . '.'));
    }

    /**
     * FSD 13.3 — "Apply LOP to all eligible employees" / "Remove LOP for
     * selected employees", operating over the exact same filtered set
     * `lopReview()` already shows. Reuses `applyLopUpdate()` per row — the
     * identical validation/recompute `updateLop()` already uses, no
     * duplicate logic.
     */
    public function bulkLopAction(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:apply_all,remove_selected'],
            'month'  => ['required', 'integer', 'between:1,12'],
            'year'   => ['required', 'integer', 'min:2020'],
            'payroll_ids'   => ['nullable', 'array'],
            'payroll_ids.*' => ['exists:payroll_records,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $query = PayrollRecord::where('month', $data['month'])->where('year', $data['year'])
            ->whereIn('status', PayrollRecord::EDITABLE_STATUSES);
        $query = BranchScope::scopeQueryVia($query, 'employee');

        if ($data['action'] === 'remove_selected' && ! empty($data['payroll_ids'])) {
            $query->whereIn('id', $data['payroll_ids']);
        }

        $count = 0;
        foreach ($query->get() as $payroll) {
            if ($data['action'] === 'apply_all') {
                if ($payroll->calculated_lop_days > 0 && ! $payroll->lop_applied) {
                    $this->applyLopUpdate($payroll, (float) $payroll->calculated_lop_days, true, $data['reason'] ?? null);
                    $count++;
                }
            } else {
                if ($payroll->lop_applied) {
                    $this->applyLopUpdate($payroll, (float) $payroll->lop_days, false, $data['reason'] ?? 'Bulk LOP removal');
                    $count++;
                }
            }
        }

        return back()->with('success', "Bulk LOP action applied to {$count} employee(s).");
    }

    /** @return 'ok'|'no_change'|'reason_required' */
    private function applyLopUpdate(PayrollRecord $payroll, float $lopDays, bool $lopApplied, ?string $reason): string
    {
        $daysChanged    = abs($lopDays - (float) $payroll->lop_days) >= 0.001;
        $appliedChanged = $lopApplied !== (bool) $payroll->lop_applied;
        if (! $daysChanged && ! $appliedChanged) {
            return 'no_change';
        }

        // FSD 12.4/13.3: "Any difference between calculated and approved LOP
        // shall require a reason."
        $differsFromCalculated = abs($lopDays - (float) $payroll->calculated_lop_days) >= 0.001;
        if ($differsFromCalculated && empty($reason)) {
            return 'reason_required';
        }

        // FSD 12.4 "Apply LOP" — unchecking zeroes the deduction regardless
        // of calculated/approved days; "Final LOP Days" = lop_days when
        // applied, 0 otherwise.
        $finalLopDays = $lopApplied ? $lopDays : 0.0;

        $workingDays = max(1, $payroll->working_days);
        $perDaySal = ($payroll->basic_salary + $payroll->hra + $payroll->da + $payroll->ta + $payroll->medical_allowance + $payroll->special_allowance)
            / 12 / $workingDays * $finalLopDays;
        // Approximation consistent with generate()'s own per-day rate basis
        // (CTC/12/workingDays) isn't available here without re-loading the
        // salary structure; gross/workingDays is the closest equivalent
        // derivable purely from the stored payroll record.
        // Same Salary Slab LOP % applied in generate() — kept consistent so
        // a manual LOP-day override doesn't diverge from the original
        // calculation's slab-based leniency factor.
        $slab = $payroll->employee?->currentSalary?->slab;
        if ($slab) {
            $perDaySal = $slab->lopDeduction($perDaySal);
        }
        $newLopDeduction = round($perDaySal, 2);
        $newTotalDeductions = $payroll->pf_employee + $payroll->esi_employee + $payroll->tds + $payroll->advance_deduction + $newLopDeduction + $payroll->other_deductions;
        $newNet = $payroll->gross_earnings + $payroll->ot_amount - $newTotalDeductions;

        $payroll->update([
            'lop_days' => $lopDays,
            'lop_applied' => $lopApplied,
            'lop_override_reason' => $reason,
            'lop_deduction' => $newLopDeduction,
            'total_deductions' => round($newTotalDeductions, 2),
            'net_salary' => round($newNet, 2),
        ]);

        return 'ok';
    }

    /**
     * FSD 12.4 — "The payroll screen shall display an LOP confirmation
     * prompt before calculation" / "before payroll processing." Bulk-locks
     * every draft record in the period; storePayment() refuses to run until
     * this has been done — the actual "before payroll processing" gate,
     * since a PayrollRecord's status only ever moves draft -> paid, and only
     * via storePayment() (there is no separate "processed" transition).
     */
    public function confirmLop(Request $request)
    {
        $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year'  => ['required', 'integer', 'min:2020'],
        ]);

        $query = PayrollRecord::where('month', $request->month)->where('year', $request->year)
            ->whereIn('status', PayrollRecord::EDITABLE_STATUSES);
        $query = BranchScope::scopeQueryVia($query, 'employee');
        $count = $query->update(['lop_confirmed_at' => now(), 'lop_confirmed_by' => auth()->id()]);

        return redirect()->route('payroll.lop-review', ['month' => $request->month, 'year' => $request->year])
            ->with('success', "LOP confirmed and locked for {$count} employee(s). Payroll processing can now proceed.");
    }

    // ── 13.6 Status lifecycle: Confirm / Close / Reopen ───────────────────

    /**
     * FSD 13.3/13.6 — "Confirm Payroll." This IS the FSD 13.3 confirmation
     * moment for this system's architecture (see plan notes): it's blocked
     * until every employee in the batch has their LOP either resolved
     * (lop_confirmed_at set) or has zero calculated LOP to begin with.
     */
    public function confirmPayroll(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year'  => ['required', 'integer', 'min:2020'],
        ]);

        // Module 11 (FSD 15.2) — "Payroll confirmation, closure, and
        // reopening shall have separate permissions." Previously shared
        // `approve`/`process` with other actions; each now has its own flag.
        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'payroll', 'confirm')) {
            abort(403, 'You do not have the "Confirm" permission for Payroll in Branch Administration.');
        }

        $query = PayrollRecord::where('month', $data['month'])->where('year', $data['year'])
            ->whereIn('status', PayrollRecord::EDITABLE_STATUSES);
        $query = BranchScope::scopeQueryVia($query, 'employee');

        $unresolvedLop = (clone $query)->where('calculated_lop_days', '>', 0)->whereNull('lop_confirmed_at')->count();
        if ($unresolvedLop > 0) {
            return redirect()->route('payroll.lop-review', ['month' => $data['month'], 'year' => $data['year']])
                ->with('error', "{$unresolvedLop} employee(s) have unconfirmed LOP. Confirm LOP for all of them before confirming payroll.");
        }

        $records = $query->get();
        foreach ($records as $payroll) {
            $old = $payroll->toArray();
            $payroll->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
            AuditLog::write(auth()->id(), 'confirm', 'payroll_records', $payroll->id, $old, $payroll->fresh()->toArray(), $payroll->employee?->branch_id);
        }

        return redirect()->route('payroll.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', "Payroll confirmed for {$records->count()} employee(s).");
    }

    /** FSD 13.6 — "Close Payroll." Confirmed -> Closed; payment can only be recorded once closed. */
    public function closePayroll(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year'  => ['required', 'integer', 'min:2020'],
        ]);

        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'payroll', 'close')) {
            abort(403, 'You do not have the "Close" permission for Payroll in Branch Administration.');
        }

        $query = PayrollRecord::where('month', $data['month'])->where('year', $data['year'])->where('status', 'confirmed');
        $query = BranchScope::scopeQueryVia($query, 'employee');

        $records = $query->get();
        foreach ($records as $payroll) {
            $old = $payroll->toArray();
            $payroll->update(['status' => 'closed', 'closed_by' => auth()->id(), 'closed_at' => now()]);
            AuditLog::write(auth()->id(), 'close', 'payroll_records', $payroll->id, $old, $payroll->fresh()->toArray(), $payroll->employee?->branch_id);
        }

        return redirect()->route('payroll.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', "Payroll closed for {$records->count()} employee(s).");
    }

    /**
     * FSD 13.6 — "Reopen payroll with permission." Requires a typed reason
     * and is always audit logged. Uses the same `process` action already
     * established for generation (Branch Administration), since reopening
     * is at least as sensitive as generating.
     */
    public function reopenPayroll(Request $request, PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);

        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'payroll', 'reopen')) {
            abort(403, 'You do not have the "Reopen" permission for Payroll in Branch Administration.');
        }

        if (! in_array($payroll->status, ['confirmed', 'closed'], true)) {
            return back()->withErrors(['reopen_reason' => 'Only confirmed or closed payroll can be reopened.']);
        }

        $data = $request->validate(['reopen_reason' => ['required', 'string', 'max:500']]);

        $old = $payroll->toArray();
        $payroll->update([
            'status' => 'calculated',
            'reopened_by' => auth()->id(),
            'reopened_at' => now(),
            'reopen_reason' => $data['reopen_reason'],
        ]);

        AuditLog::write(auth()->id(), 'reopen', 'payroll_records', $payroll->id, $old, $payroll->fresh()->toArray(), $payroll->employee?->branch_id, $data['reopen_reason']);

        return back()->with('success', 'Payroll reopened for ' . $payroll->employee->full_name . '.');
    }

    // ── 13.6/13.7 Exports ─────────────────────────────────────────────────

    /** FSD 13.6 — "Export payroll register." */
    public function exportRegister(Request $request)
    {
        $records = $this->periodQuery($request)->with(['employee.department'])->get();

        return $this->streamCsv('payroll-register-' . $request->input('month') . '-' . $request->input('year') . '.csv',
            ['Employee Number', 'Employee', 'Department', 'Payroll Days', 'Paid Days', 'LOP Days', 'Gross Earnings', 'Total Deductions', 'Net Salary', 'Employer Cost', 'Status'],
            $records->map(fn($r) => [
                $r->employee->employee_code ?? '', $r->employee->full_name ?? '', $r->employee->department->name ?? '',
                $r->working_days, $r->paid_days, $r->lop_applied ? $r->lop_days : 0,
                $r->gross_earnings, $r->total_deductions, $r->net_salary, $r->employer_cost, $r->status_label,
            ])->all()
        );
    }

    /** FSD 13.6 — "Generate bank transfer statement." Unmasked (for actual bank processing) — gated by export_excel since this is genuinely sensitive, unlike the masked payslip. */
    public function exportBankTransfer(Request $request)
    {
        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'payroll', 'export_excel')) {
            abort(403, 'You do not have the "Export Excel" permission for Payroll in Branch Administration.');
        }

        $records = $this->periodQuery($request)->with(['employee.bankDetails'])->get()
            ->filter(fn($r) => $r->employee?->bankDetails->first()?->payment_mode === 'bank_transfer' || $r->employee?->bankDetails->isNotEmpty());

        return $this->streamCsv('bank-transfer-statement-' . $request->input('month') . '-' . $request->input('year') . '.csv',
            ['Employee Number', 'Employee Name', 'Bank Name', 'Account Number', 'IFSC Code', 'Net Salary'],
            $records->map(function ($r) {
                $bank = $r->employee->bankDetails->first();
                return [
                    $r->employee->employee_code ?? '', $r->employee->full_name ?? '',
                    $bank?->bank_name ?? '', $bank?->account_number ?? '', $bank?->ifsc_code ?? '', $r->net_salary,
                ];
            })->all()
        );
    }

    /** FSD 13.6 — "Generate statutory reports" (PF/ESI/TDS employee+employer breakdown). */
    public function exportStatutory(Request $request)
    {
        $records = $this->periodQuery($request)->with(['employee'])->get();

        return $this->streamCsv('statutory-report-' . $request->input('month') . '-' . $request->input('year') . '.csv',
            ['Employee Number', 'Employee Name', 'PF Number', 'UAN', 'ESI Number', 'PF Employee', 'PF Employer', 'ESI Employee', 'ESI Employer', 'TDS'],
            $records->map(fn($r) => [
                $r->employee->employee_code ?? '', $r->employee->full_name ?? '',
                $r->employee->pf_number ?? '', $r->employee->uan_number ?? '', $r->employee->esi_number ?? '',
                $r->pf_employee, $r->pf_employer, $r->esi_employee, $r->esi_employer, $r->tds,
            ])->all()
        );
    }

    private function periodQuery(Request $request)
    {
        $request->validate(['month' => ['required', 'integer', 'between:1,12'], 'year' => ['required', 'integer', 'min:2020']]);
        $query = PayrollRecord::where('month', $request->month)->where('year', $request->year);
        return BranchScope::scopeQueryVia($query, 'employee');
    }

    private function streamCsv(string $filename, array $headers, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) { fputcsv($handle, $row); }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
