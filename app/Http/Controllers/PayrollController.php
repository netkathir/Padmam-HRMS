<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\PayrollPayment;
use App\Models\EmployeeSalaryStructure;
use App\Models\Attendance;
use App\Models\PfEsiConfig;
use App\Models\SalarySlab;
use App\Support\BranchScope;
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
        $workingDays = $this->getWorkingDays($month, $year);

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

            $employeeTypeKey = $employee->primary_employee_type === 'staff' ? 'staff' : $employee->labour_type;
            $slab = SalarySlab::findApplicable((float) $salary->ctc, $employee->primary_employee_type, $employee->labour_type, $employee->branch_id, $periodDate);

            if (! $slab && $employeeTypeKey && in_array($employeeTypeKey, $slabbedEmployeeTypes, true)) {
                $noSlabEmployees[] = $employee->full_name;
                $errors++;
                continue;
            }

            // Attendance summary
            $presentDays = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $month)->whereYear('date', $year)
                ->whereIn('status', ['present', 'half_day'])->count();

            $lopDays   = $workingDays - $presentDays;
            $perDaySal = $lopDays > 0 ? ($salary->ctc / 12) / $workingDays * $lopDays : 0;

            $gross = $salary->basic_salary + $salary->hra + $salary->da
                   + $salary->ta + $salary->medical_allowance + $salary->special_allowance;
            $net   = $gross - $perDaySal;

            // PF / ESI / TDS — prefer the matched Salary Slab's percentages
            // (FSD 7.5); fall back to the global PfEsiConfig exactly as
            // before when no slab applies to this employee.
            $pfEmp = $pfEmpEr = $esiEmp = $esiEmpEr = $tds = 0;
            $pfEmployeePct  = $slab->pf_employee_percentage  ?? $pfEsi->pf_employee_pct  ?? null;
            $pfEmployerPct  = $slab->pf_employer_percentage  ?? $pfEsi->pf_employer_pct  ?? null;
            $esiEmployeePct = $slab->esi_employee_percentage ?? $pfEsi->esi_employee_pct ?? null;
            $esiEmployerPct = $slab->esi_employer_percentage ?? $pfEsi->esi_employer_pct ?? null;

            if ($employee->is_pf_applicable && $pfEmployeePct !== null) {
                $wageBase = min($salary->basic_salary, $pfEsi->pf_wage_ceiling ?? $salary->basic_salary);
                $pfEmp   = $wageBase * ($pfEmployeePct / 100);
                $pfEmpEr = $wageBase * ($pfEmployerPct / 100);
            }
            if ($employee->is_esi_applicable && $esiEmployeePct !== null && $gross <= ($pfEsi->esi_wage_ceiling ?? 21000)) {
                $esiEmp   = $gross * ($esiEmployeePct / 100);
                $esiEmpEr = $gross * ($esiEmployerPct / 100);
            }
            if ($employee->is_tds_applicable && $slab && $slab->tds_percentage) {
                $tds = $gross * ($slab->tds_percentage / 100);
            }

            $totalDeductions = $pfEmp + $esiEmp + $tds;
            $netSalary       = $net - $totalDeductions;

            DB::transaction(function () use ($employee, $salary, $month, $year, $gross, $net, $netSalary, $workingDays, $presentDays, $lopDays, $pfEmp, $pfEmpEr, $esiEmp, $esiEmpEr, $tds, $totalDeductions) {
                PayrollRecord::create([
                    'employee_id'      => $employee->id,
                    'month'            => $month,
                    'year'             => $year,
                    'working_days'     => $workingDays,
                    'present_days'     => $presentDays,
                    'absent_days'      => max(0, $workingDays - $presentDays),
                    'lop_days'         => max(0, $lopDays),
                    'ot_hours'         => 0,
                    'basic_salary'     => $salary->basic_salary,
                    'hra'              => $salary->hra,
                    'da'               => $salary->da,
                    'ta'               => $salary->ta,
                    'medical_allowance'=> $salary->medical_allowance,
                    'special_allowance'=> $salary->special_allowance,
                    'ot_amount'        => 0,
                    'other_earnings'   => 0,
                    'gross_earnings'   => round($gross, 2),
                    'pf_employee'      => round($pfEmp, 2),
                    'pf_employer'      => round($pfEmpEr, 2),
                    'esi_employee'     => round($esiEmp, 2),
                    'esi_employer'     => round($esiEmpEr, 2),
                    'tds'              => round($tds, 2),
                    'advance_deduction'=> 0,
                    'lop_deduction'    => round($perDaySal, 2),
                    'other_deductions' => 0,
                    'total_deductions' => round($totalDeductions, 2),
                    'net_salary'       => round($netSalary, 2),
                    'status'           => 'draft',
                    'generated_by'     => auth()->id(),
                    'generated_at'     => now(),
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

    private function getWorkingDays(int $month, int $year): int
    {
        $days = 0;
        $date = \Carbon\Carbon::create($year, $month, 1);
        $end  = $date->copy()->endOfMonth();

        while ($date->lte($end)) {
            if (! in_array($date->dayOfWeek, [0, 6])) $days++;
            $date->addDay();
        }

        return $days;
    }
}
