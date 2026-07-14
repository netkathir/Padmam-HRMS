<p>Dear {{ $payroll->employee->full_name ?? '' }},</p>
<p>Please find attached your payslip for {{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}.</p>
<p>This is a system-generated email. Please contact HR for any queries regarding this payslip.</p>
