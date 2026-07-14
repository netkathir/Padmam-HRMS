<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\PayrollPayment;
use App\Models\EmployeeSalaryStructure;
use App\Models\Attendance;
use App\Models\BusinessRule;
use App\Models\PfEsiConfig;
use App\Models\SalarySlab;
use App\Support\BranchScope;
use App\Support\RuleEngine;
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
            ->orderBy('created_at', 'desc')
            ->paginate(25)->withQueryString();

        $summary = BranchScope::scopeQueryVia(
            PayrollRecord::where('month', $month)->where('year', $year), 'employee'
        )
            ->selectRaw('COUNT(*) as count, SUM(gross_earnings) as gross, SUM(net_salary) as net, SUM(total_deductions) as deductions')
            ->first();

        return view('payroll.index', compact('records', 'summary', 'month', 'year'));
    }

    public function generateForm()
    {
        $departments = BranchScope::scopeQuery(\App\Models\Department::query())->orderBy('name')->get();

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

        return view('payroll.generate', compact('departments', 'recentPayrolls'));
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

        $employees  = $query->get();
        // The config actually effective for this payroll period (by
        // effective_from), not simply the most-recently-created row — a
        // retroactive/back-dated run must apply the rates that were in
        // force at the time, not whatever was configured most recently.
        $periodDate = \Carbon\Carbon::create($year, $month, 1)->toDateString();
        $pfEsi      = PfEsiConfig::effectiveOn($periodDate);

        // FSD 7.5 — "the applicable slab shall be automatically selected...
        // If no applicable slab is found, the system shall show a validation
        // message before payroll processing." Only enforced for employee
        // types that have at least one active slab defined at all; an
        // employee type with none configured yet falls back to PfEsiConfig
        // exactly as before this feature existed (no regression for
        // deployments that haven't configured Salary Slabs).
        $slabbedEmployeeTypes = SalarySlab::where('is_active', true)->get()
            ->flatMap(fn($s) => $s->applicable_employee_types ?? ['staff', 'company_labour', 'contract_labour'])
            ->unique()->all();

        $generated = $skipped = $errors = 0;
        $noSlabEmployees = [];

        foreach ($employees as $employee) {
            if ($employee->branch && ! $employee->branch->is_active) { $skipped++; continue; }

            $salary = $employee->currentSalary;
            if (! $salary) { $errors++; continue; }

            // Skip if already generated
            if (PayrollRecord::where('employee_id', $employee->id)->where('month', $month)->where('year', $year)->exists()) {
                $skipped++; continue;
            }

            $primaryType = $employee->primary_employee_type;
            $labourType  = $employee->labour_type;
            $branchId    = $employee->branch_id;
            $contractorId = $employee->contractor_id;

            $employeeTypeKey = $primaryType === 'staff' ? 'staff' : $labourType;
            $slab = SalarySlab::findApplicable((float) $salary->ctc, $primaryType, $labourType, $branchId, $periodDate);

            if (! $slab && $employeeTypeKey && in_array($employeeTypeKey, $slabbedEmployeeTypes, true)) {
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

            // Module 4 FSD 8.6 — LOP Rule
            [$lopDays, $lopRuleId] = $this->calculateLop($employee, $month, $year, $workingDays, $branchId, $primaryType, $labourType, $contractorId, $periodDate);
            if ($lopRuleId) $appliedRules['lop'] = $lopRuleId;
            $perDaySal = $lopDays > 0 ? ($salary->ctc / 12) / $workingDays * $lopDays : 0;

            $gross = $salary->basic_salary + $salary->hra + $salary->da
                   + $salary->ta + $salary->medical_allowance + $salary->special_allowance;
            $net   = $gross - $perDaySal;

            // Module 4 FSD 8.7/8.8/8.9 — PF/ESI/TDS Rules take precedence
            // over the Module-3 Salary Slab percentages, which take
            // precedence over the global PfEsiConfig — exactly the same
            // fallback chain SalarySlab itself introduced, extended one
            // level further. A deployment with no Rule Engine PF/ESI/TDS
            // rules configured behaves exactly as before this feature.
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

            $pfEmployeePct  = $pfDetail->employee_pf_percentage  ?? $slab->pf_employee_percentage  ?? $pfEsi->pf_employee_pct  ?? null;
            $pfEmployerPct  = $pfDetail->employer_pf_percentage  ?? $slab->pf_employer_percentage  ?? $pfEsi->pf_employer_pct  ?? null;
            $esiEmployeePct = $esiDetail->employee_esi_percentage ?? $slab->esi_employee_percentage ?? $pfEsi->esi_employee_pct ?? null;
            $esiEmployerPct = $esiDetail->employer_esi_percentage ?? $slab->esi_employer_percentage ?? $pfEsi->esi_employer_pct ?? null;
            $pfWageCeiling  = $pfDetail->pf_wage_ceiling ?? $pfEsi->pf_wage_ceiling ?? null;
            $esiWageCeiling = $esiDetail->salary_slab_to ?? $pfEsi->esi_wage_ceiling ?? 21000;

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
            $tdsPct = $tdsDetail->tds_percentage ?? $slab->tds_percentage ?? null;
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

            $presentDays = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $month)->whereYear('date', $year)
                ->where('status', 'present')->count();

            DB::transaction(function () use (
                $employee, $salary, $month, $year, $gross, $net, $netSalary, $workingDays, $presentDays,
                $lopDays, $pfEmp, $pfEmpEr, $esiEmp, $esiEmpEr, $tds, $totalDeductions, $otHours, $otAmount, $appliedRules
            ) {
                PayrollRecord::create([
                    'employee_id'      => $employee->id,
                    'month'            => $month,
                    'year'             => $year,
                    'working_days'     => $workingDays,
                    'present_days'     => $presentDays,
                    'absent_days'      => max(0, $workingDays - $presentDays),
                    'lop_days'         => round($lopDays, 2),
                    'calculated_lop_days' => round($lopDays, 2),
                    'ot_hours'         => round($otHours, 2),
                    'basic_salary'     => $salary->basic_salary,
                    'hra'              => $salary->hra,
                    'da'               => $salary->da,
                    'ta'               => $salary->ta,
                    'medical_allowance'=> $salary->medical_allowance,
                    'special_allowance'=> $salary->special_allowance,
                    'ot_amount'        => round($otAmount, 2),
                    'other_earnings'   => 0,
                    'gross_earnings'   => round($gross, 2),
                    'pf_employee'      => round($pfEmp, 2),
                    'pf_employer'      => round($pfEmpEr, 2),
                    'esi_employee'     => round($esiEmp, 2),
                    'esi_employer'     => round($esiEmpEr, 2),
                    'tds'              => round($tds, 2),
                    'advance_deduction'=> 0,
                    'lop_deduction'    => round($gross - $net, 2),
                    'other_deductions' => 0,
                    'total_deductions' => round($totalDeductions, 2),
                    'net_salary'       => round($netSalary, 2),
                    'status'           => 'draft',
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

        return redirect()->route('payroll.index', ['month' => $month, 'year' => $year])
            ->with($noSlabEmployees ? 'error' : 'success', $message);
    }

    /**
     * Module 4 FSD 8.6 — LOP Rule. Returns [lopDays, ruleId|null]. Falls
     * back to the original `workingDays - (present+half_day count)` when no
     * LOP Rule is configured, preserving today's behavior exactly.
     */
    private function calculateLop(Employee $employee, int $month, int $year, int $workingDays, ?int $branchId, ?string $primaryType, ?string $labourType, ?int $contractorId, string $periodDate): array
    {
        $rule = BusinessRule::resolveForEmployee($employee, 'lop', $branchId, $primaryType, $labourType, $contractorId, $periodDate);
        $detail = $rule?->lopRule;

        $attendanceQuery = fn() => Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)->whereYear('date', $year);

        if (! $detail) {
            $presentDays = $attendanceQuery()->whereIn('status', ['present', 'half_day'])->count();
            return [max(0, $workingDays - $presentDays), null];
        }

        $presentCount   = $attendanceQuery()->where('status', 'present')->count();
        $halfDayCount   = $attendanceQuery()->where('status', 'half_day')->count();
        $explicitAbsent = $attendanceQuery()->where('status', 'absent')->count();
        // Working days with no attendance record at all (never marked, or a
        // missing punch that was never resolved to an explicit status) —
        // the old fallback treated every such day as LOP; "Missing Punch as
        // LOP" is this rule's configurable equivalent of that same gap.
        $unrecordedDays = max(0, $workingDays - $presentCount - $halfDayCount - $explicitAbsent);

        $lopDays = $halfDayCount * (float) $detail->half_day_lop_value;
        if ($detail->absent_day_as_lop) {
            $lopDays += $explicitAbsent * (float) $detail->full_day_lop_value;
        }
        if ($detail->missing_punch_as_lop) {
            $lopDays += $unrecordedDays * (float) $detail->full_day_lop_value;
        }

        if ($detail->late_count_conversion) {
            $lateCount = $attendanceQuery()->where('is_late', true)->count();
            $lopDays += intdiv($lateCount, $detail->late_count_conversion) * (float) $detail->half_day_lop_value;
        }
        if ($detail->early_exit_conversion) {
            $earlyCount = $attendanceQuery()->where('is_early_exit', true)->count();
            $lopDays += intdiv($earlyCount, $detail->early_exit_conversion) * (float) $detail->half_day_lop_value;
        }

        return [max(0, $lopDays), $rule->id];
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
        $payroll->load(['employee.branch', 'employee.department', 'employee.designation', 'allowances', 'deductions', 'payments']);
        return view('payroll.payslip', compact('payroll'));
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
        $data = $request->validate([
            'payment_date'     => ['required', 'date'],
            'payment_mode'     => ['required', 'in:bank_transfer,cash,cheque,upi'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'remarks'          => ['nullable', 'string'],
        ]);

        $data['payroll_id']  = $payroll->id;
        $data['created_by']  = auth()->id();

        PayrollPayment::create($data);
        $payroll->update(['status' => 'paid']);

        return redirect()->route('payroll.payslip', $payroll)->with('success', 'Payment recorded.');
    }

    /**
     * Module 4 FSD 8.5 — Weekly Off Rule drives which days are excluded from
     * "working days" (falls back to the branch's own weekly_off_days, then
     * to the original hardcoded Sat/Sun exclusion when neither is configured).
     */
    private function getWorkingDays(int $month, int $year, ?array $weeklyOffDays = null): int
    {
        $dayNameToIndex = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $offIndexes = $weeklyOffDays
            ? array_values(array_filter(array_map(fn($d) => $dayNameToIndex[$d] ?? null, $weeklyOffDays), fn($v) => $v !== null))
            : [0, 6];

        $days = 0;
        $date = \Carbon\Carbon::create($year, $month, 1);
        $end  = $date->copy()->endOfMonth();

        while ($date->lte($end)) {
            if (! in_array($date->dayOfWeek, $offIndexes, true)) $days++;
            $date->addDay();
        }

        return $days;
    }

    /**
     * Module 4 FSD 8.6 — "system shall display a prompt allowing the
     * payroll user to select ... Any LOP override shall require a reason."
     * Lists a batch's just-generated draft payrolls for review/adjustment.
     */
    public function lopReview(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $records = BranchScope::scopeQueryVia(
            PayrollRecord::with(['employee.department'])
                ->where('month', $month)->where('year', $year)->where('status', 'draft'),
            'employee'
        )->orderBy('employee_id')->paginate(30)->withQueryString();

        return view('payroll.lop-review', compact('records', 'month', 'year'));
    }

    public function updateLop(Request $request, PayrollRecord $payroll)
    {
        BranchScope::assertBranchAccess($payroll->employee?->branch_id);
        $data = $request->validate([
            'lop_days' => ['required', 'numeric', 'min:0'],
            'lop_override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (abs((float) $data['lop_days'] - (float) $payroll->lop_days) < 0.001) {
            return back()->with('success', 'No change.');
        }

        // FSD 8.6: "Any LOP override shall require a reason."
        if (empty($data['lop_override_reason'])) {
            return back()->withErrors(['lop_override_reason' => 'A reason is required when overriding the calculated LOP.'])->withInput();
        }

        $workingDays = max(1, $payroll->working_days);
        $perDaySal = ($payroll->basic_salary + $payroll->hra + $payroll->da + $payroll->ta + $payroll->medical_allowance + $payroll->special_allowance)
            / 12 / $workingDays * $data['lop_days'];
        // Approximation consistent with generate()'s own per-day rate basis
        // (CTC/12/workingDays) isn't available here without re-loading the
        // salary structure; gross/workingDays is the closest equivalent
        // derivable purely from the stored payroll record.
        $newLopDeduction = round($perDaySal, 2);
        $newTotalDeductions = $payroll->pf_employee + $payroll->esi_employee + $payroll->tds + $payroll->advance_deduction + $newLopDeduction + $payroll->other_deductions;
        $newNet = $payroll->gross_earnings + $payroll->ot_amount - $newTotalDeductions;

        $payroll->update([
            'lop_days' => $data['lop_days'],
            'lop_override_reason' => $data['lop_override_reason'] ?? null,
            'lop_deduction' => $newLopDeduction,
            'total_deductions' => round($newTotalDeductions, 2),
            'net_salary' => round($newNet, 2),
        ]);

        return back()->with('success', 'LOP updated for ' . $payroll->employee->full_name . '.');
    }
}
