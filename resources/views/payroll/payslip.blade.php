@extends('layouts.app')
@section('title','Payslip')
@section('page-title','Payslip')
@section('page-subtitle',($payroll->employee->full_name ?? 'Employee') . ' — ' . \Carbon\Carbon::create($payroll->year, $payroll->month)->format('F Y'))
@section('page-actions')
    <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> Print</button>
    <a href="{{ route('payroll.payment', $payroll) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-cash-coin"></i> Payment</a>
@endsection
@section('content')
<div class="card" id="payslip-card">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="text-primary">{{ config('app.company_name', 'Company Name') }}</h5>
                <small class="text-muted">{{ config('app.company_address', '') }}</small>
            </div>
            <div class="col-md-6 text-end">
                <h6>PAYSLIP</h6>
                <span class="badge bg-primary-subtle text-primary fs-6">{{ \Carbon\Carbon::create($payroll->year, $payroll->month)->format('F Y') }}</span>
            </div>
        </div>
        <hr>
        <div class="row mb-4">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Employee ID</dt><dd class="col-sm-7">{{ $payroll->employee->employee_code }}</dd>
                    <dt class="col-sm-5">Name</dt><dd class="col-sm-7">{{ $payroll->employee->full_name ?? '—' }}</dd>
                    <dt class="col-sm-5">Department</dt><dd class="col-sm-7">{{ $payroll->employee->department->name ?? '—' }}</dd>
                    <dt class="col-sm-5">Designation</dt><dd class="col-sm-7">{{ $payroll->employee->designation->name ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Pay Period</dt><dd class="col-sm-7">{{ \Carbon\Carbon::create($payroll->year, $payroll->month)->format('F Y') }}</dd>
                    <dt class="col-sm-5">Working Days</dt><dd class="col-sm-7">{{ $payroll->working_days }}</dd>
                    <dt class="col-sm-5">Days Worked</dt><dd class="col-sm-7">{{ $payroll->days_worked }}</dd>
                    <dt class="col-sm-5">Bank Account</dt><dd class="col-sm-7">{{ $payroll->employee->bank_account_no ? '•••• ' . substr($payroll->employee->bank_account_no, -4) : '—' }}</dd>
                </dl>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <thead class="table-success"><tr><th>Earnings</th><th class="text-end">Amount (₹)</th></tr></thead>
                    <tbody>
                        @foreach($payroll->earnings ?? [] as $earning)
                        <tr><td>{{ $earning['name'] }}</td><td class="text-end">{{ number_format($earning['amount'], 2) }}</td></tr>
                        @endforeach
                        <tr class="table-light fw-bold"><td>Gross Earnings</td><td class="text-end">{{ number_format($payroll->gross_salary, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <thead class="table-danger"><tr><th>Deductions</th><th class="text-end">Amount (₹)</th></tr></thead>
                    <tbody>
                        @foreach($payroll->deductions ?? [] as $deduction)
                        <tr><td>{{ $deduction['name'] }}</td><td class="text-end">{{ number_format($deduction['amount'], 2) }}</td></tr>
                        @endforeach
                        <tr class="table-light fw-bold"><td>Total Deductions</td><td class="text-end">{{ number_format($payroll->total_deductions, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 offset-md-6">
                <table class="table table-bordered table-sm">
                    <tr class="table-primary fw-bold"><td>NET PAY</td><td class="text-end fs-5">₹{{ number_format($payroll->net_salary, 2) }}</td></tr>
                </table>
                <p class="text-muted small text-end">Amount in words: <em>{{ $payroll->net_salary_words ?? '' }}</em></p>
            </div>
        </div>
        <p class="text-center text-muted small mt-4 mb-0">This is a computer-generated payslip and does not require a signature.</p>
    </div>
</div>
@endsection
@push('styles')
<style>
@media print {
    .navbar, .sidebar, .page-actions, .btn, nav[aria-label] { display: none !important; }
    #payslip-card { border: none !important; }
}
</style>
@endpush
