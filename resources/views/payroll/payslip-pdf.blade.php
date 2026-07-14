<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #222; }
        .header { display: table; width: 100%; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px; }
        .header .left, .header .right { display: table-cell; vertical-align: top; }
        .header .right { text-align: right; }
        .header img { height: 40px; }
        h3, h4 { margin: 0 0 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
        .info-table td { border: none; padding: 2px 6px 2px 0; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .net-pay { background: #eaf3ff; font-size: 13px; }
        .footer { margin-top: 20px; text-align: center; color: #666; border-top: 1px solid #ccc; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $employee = $payroll->employee;
        // Module 11 (FSD 15.2) — same dedicated View Sensitive Data gate as
        // the on-screen payslip and Employee profile (previously masked
        // unconditionally here with no bypass at all).
        $canViewSensitive = $canViewSensitive ?? \App\Support\SensitiveDataAccess::canView('payroll');
        $mask = fn($v) => $v ? ($canViewSensitive ? $v : str_repeat('*', max(0, strlen($v) - 4)) . substr($v, -4)) : '-';
        $bankAccountNumber = $employee->bankDetails->first();
        $showEmployerContribution = \App\Models\Setting::get('payroll', 'show_employer_contribution_on_payslip', true);
    @endphp
    <div class="header">
        <div class="left">
            @if($company?->logo_path)
                <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="Logo">
            @endif
            <h3>{{ $company->name ?? config('app.name') }}</h3>
            <div>{{ $employee->branch->name ?? '' }}</div>
        </div>
        <div class="right">
            <h4>PAYSLIP</h4>
            <div>{{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}</div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Employee Number:</strong> {{ $employee->employee_code }}</td>
            <td><strong>Employee Name:</strong> {{ $employee->full_name }}</td>
        </tr>
        <tr>
            <td><strong>Employee Type:</strong> {{ $employee->employeeType->name ?? '-' }}</td>
            <td><strong>Department:</strong> {{ $employee->department->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Designation:</strong> {{ $employee->designation->name ?? '-' }}</td>
            <td><strong>Date of Joining:</strong> {{ $employee->date_of_joining?->format('d M Y') ?? '-' }}</td>
        </tr>
        @if($employee->contractor)
        <tr><td colspan="2"><strong>Contractor:</strong> {{ $employee->contractor->name }}</td></tr>
        @endif
        <tr>
            <td><strong>Bank Account:</strong> {{ $bankAccountNumber ? ($canViewSensitive ? $bankAccountNumber->account_number : $bankAccountNumber->masked_account_number) : '-' }}</td>
            <td><strong>PAN:</strong> {{ $mask($employee->pan_number) }}</td>
        </tr>
        <tr>
            <td><strong>UAN:</strong> {{ $mask($employee->uan_number) }}</td>
            <td><strong>PF Number:</strong> {{ $mask($employee->pf_number) }}</td>
        </tr>
        <tr>
            <td><strong>ESI Number:</strong> {{ $mask($employee->esi_number) }}</td>
            <td></td>
        </tr>
        <tr>
            <td><strong>Payroll Days:</strong> {{ $payroll->working_days }}</td>
            <td><strong>Paid Days:</strong> {{ $payroll->paid_days }}</td>
        </tr>
        <tr>
            <td><strong>LOP Days:</strong> {{ $payroll->lop_applied ? $payroll->lop_days : 0 }}</td>
            <td></td>
        </tr>
    </table>

    <table>
        <tr><th colspan="2">Earnings</th><th colspan="2">Deductions</th></tr>
        <tr>
            <td>Basic Salary</td><td class="text-end">{{ number_format($payroll->basic_salary, 2) }}</td>
            <td>LOP Deduction</td><td class="text-end">{{ number_format($payroll->lop_deduction, 2) }}</td>
        </tr>
        <tr>
            <td>HRA</td><td class="text-end">{{ number_format($payroll->hra, 2) }}</td>
            <td>Employee PF</td><td class="text-end">{{ number_format($payroll->pf_employee, 2) }}</td>
        </tr>
        <tr>
            <td>DA</td><td class="text-end">{{ number_format($payroll->da, 2) }}</td>
            <td>Employee ESI</td><td class="text-end">{{ number_format($payroll->esi_employee, 2) }}</td>
        </tr>
        <tr>
            <td>TA</td><td class="text-end">{{ number_format($payroll->ta, 2) }}</td>
            <td>TDS</td><td class="text-end">{{ number_format($payroll->tds, 2) }}</td>
        </tr>
        <tr>
            <td>Medical Allowance</td><td class="text-end">{{ number_format($payroll->medical_allowance, 2) }}</td>
            <td>Advance Deduction</td><td class="text-end">{{ number_format($payroll->advance_deduction, 2) }}</td>
        </tr>
        <tr>
            <td>Special Allowance</td><td class="text-end">{{ number_format($payroll->special_allowance, 2) }}</td>
            <td>Other Deductions</td><td class="text-end">{{ number_format($payroll->other_deductions, 2) }}</td>
        </tr>
        <tr>
            <td>Overtime Earnings</td><td class="text-end">{{ number_format($payroll->ot_amount, 2) }}</td>
            <td></td><td></td>
        </tr>
        <tr class="fw-bold">
            <td>Gross Earnings</td><td class="text-end">{{ number_format($payroll->gross_earnings, 2) }}</td>
            <td>Total Deductions</td><td class="text-end">{{ number_format($payroll->total_deductions, 2) }}</td>
        </tr>
    </table>

    @if($showEmployerContribution)
    <table>
        <tr><th colspan="2">Employer Contributions</th></tr>
        <tr><td>Employer PF</td><td class="text-end">{{ number_format($payroll->pf_employer, 2) }}</td></tr>
        <tr><td>Employer ESI</td><td class="text-end">{{ number_format($payroll->esi_employer, 2) }}</td></tr>
        <tr class="fw-bold"><td>Total Employer Cost</td><td class="text-end">{{ number_format($payroll->employer_cost, 2) }}</td></tr>
    </table>
    @endif

    <table>
        <tr class="net-pay fw-bold"><td>NET SALARY</td><td class="text-end">Rs. {{ number_format($payroll->net_salary, 2) }}</td></tr>
    </table>
    <p><em>{{ \App\Support\NumberToWords::convert((float) $payroll->net_salary) }}</em></p>

    <div class="footer">This is a system-generated payslip and does not require a signature.</div>
</body>
</html>
