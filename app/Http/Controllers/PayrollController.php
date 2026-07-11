<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\PayrollPayment;
use App\Models\EmployeeSalaryStructure;
use App\Models\Attendance;
use App\Models\PfEsiConfig;
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
        $departments = \App\Models\Department::orderBy('name')->get();

        $recentPayrolls = PayrollRecord::selectRaw(
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
        $pfEsi      = PfEsiConfig::where('is_active', true)->latest()->first();
        $workingDays = $this->getWorkingDays($month, $year);

        $generated = $skipped = $errors = 0;

        foreach ($employees as $employee) {
            if ($employee->branch && ! $employee->branch->is_active) { $skipped++; continue; }

            $salary = $employee->currentSalary;
            if (! $salary) { $errors++; continue; }

            // Skip if already generated
            if (PayrollRecord::where('employee_id', $employee->id)->where('month', $month)->where('year', $year)->exists()) {
                $skipped++; continue;
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

            // PF / ESI
            $pfEmp = $pfEmpEr = $esiEmp = $esiEmpEr = 0;
            if ($pfEsi && $employee->is_pf_applicable) {
                $pfEmp  = min($salary->basic, $pfEsi->pf_ceiling ?? $salary->basic) * ($pfEsi->pf_employee_percent / 100);
                $pfEmpEr = min($salary->basic, $pfEsi->pf_ceiling ?? $salary->basic) * ($pfEsi->pf_employer_percent / 100);
            }
            if ($pfEsi && $employee->is_esi_applicable && $gross <= ($pfEsi->esi_ceiling ?? 21000)) {
                $esiEmp   = $gross * ($pfEsi->esi_employee_percent / 100);
                $esiEmpEr = $gross * ($pfEsi->esi_employer_percent / 100);
            }

            $totalDeductions = $pfEmp + $esiEmp;
            $netSalary       = $net - $totalDeductions;

            DB::transaction(function () use ($employee, $salary, $month, $year, $gross, $net, $netSalary, $workingDays, $presentDays, $lopDays, $pfEmp, $pfEmpEr, $esiEmp, $esiEmpEr, $totalDeductions) {
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
                    'tds'              => 0,
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

        return redirect()->route('payroll.index', ['month' => $month, 'year' => $year])
            ->with('success', "Payroll processed: {$generated} generated, {$skipped} skipped, {$errors} errors.");
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
