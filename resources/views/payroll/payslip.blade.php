@extends('layouts.app')
@section('title', 'Payslip')
@section('page-title', 'Payslip')
@section('page-subtitle', ($payroll->employee->full_name ?? 'Employee') . ' — ' . \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y'))
@section('page-actions')
    <a href="{{ route('payroll.payslip.pdf', $payroll) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
    @if(\App\Models\Setting::get('payroll','payslip_email_enabled', false))
    <form action="{{ route('payroll.payslip.email', $payroll) }}" method="POST" class="d-inline">
        @csrf
        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope"></i> Email</button>
    </form>
    @endif
    <a href="{{ route('payroll.payment', $payroll) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-cash-coin"></i> Payment</a>
    <a href="{{ route('payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Payroll</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@php
    $employee = $payroll->employee;
    $mask = fn($v) => $v ? str_repeat('•', max(0, strlen($v) - 4)) . substr($v, -4) : '—';
    $showEmployerContribution = \App\Models\Setting::get('payroll', 'show_employer_contribution_on_payslip', true);
@endphp
<div class="card" id="payslip-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
            <div>
                @if($company?->logo_path)
                    <img src="{{ Storage::url($company->logo_path) }}" alt="Logo" style="height:48px" class="mb-2"><br>
                @endif
                <h5 class="mb-0 text-primary">{{ $company->name ?? config('app.name') }}</h5>
                <small class="text-muted">{{ $employee->branch->name ?? '—' }}</small>
            </div>
            <div class="text-end">
                <h6 class="mb-0">PAYSLIP</h6>
                <span class="badge bg-primary-subtle text-primary fs-6">{{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}</span>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4"><strong>Employee Number:</strong> {{ $employee->employee_code }}</div>
            <div class="col-md-4"><strong>Employee Name:</strong> {{ $employee->full_name }}</div>
            <div class="col-md-4"><strong>Employee Type:</strong> {{ $employee->employeeType->name ?? '—' }}</div>
            @if($employee->labour_type)
            <div class="col-md-4"><strong>Labour Type:</strong> {{ ucfirst(str_replace('_',' ',$employee->labour_type)) }}</div>
            @endif
            @if($employee->contractor)
            <div class="col-md-4"><strong>Contractor:</strong> {{ $employee->contractor->name }}</div>
            @endif
            <div class="col-md-4"><strong>Department:</strong> {{ $employee->department->name ?? '—' }}</div>
            <div class="col-md-4"><strong>Designation:</strong> {{ $employee->designation->name ?? '—' }}</div>
            <div class="col-md-4"><strong>Date of Joining:</strong> {{ $employee->date_of_joining?->format('d M Y') ?? '—' }}</div>
            <div class="col-md-4"><strong>Bank Account:</strong> {{ $employee->bankDetails->first()?->masked_account_number ?? '—' }}</div>
            <div class="col-md-4"><strong>PAN:</strong> {{ $mask($employee->pan_number) }}</div>
            <div class="col-md-4"><strong>UAN:</strong> {{ $mask($employee->uan_number) }}</div>
            <div class="col-md-4"><strong>PF Number:</strong> {{ $mask($employee->pf_number) }}</div>
            <div class="col-md-4"><strong>ESI Number:</strong> {{ $mask($employee->esi_number) }}</div>
            <div class="col-md-4"><strong>Payroll Days:</strong> {{ $payroll->working_days }}</div>
            <div class="col-md-4"><strong>Paid Days:</strong> {{ $payroll->paid_days }}</div>
            <div class="col-md-4"><strong>LOP Days:</strong> {{ $payroll->lop_applied ? $payroll->lop_days : 0 }}</div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <thead class="table-success"><tr><th>Earnings</th><th class="text-end">Amount (₹)</th></tr></thead>
                    <tbody>
                        <tr><td>Basic Salary</td><td class="text-end">{{ number_format($payroll->basic_salary, 2) }}</td></tr>
                        <tr><td>HRA</td><td class="text-end">{{ number_format($payroll->hra, 2) }}</td></tr>
                        <tr><td>DA</td><td class="text-end">{{ number_format($payroll->da, 2) }}</td></tr>
                        <tr><td>TA</td><td class="text-end">{{ number_format($payroll->ta, 2) }}</td></tr>
                        <tr><td>Medical Allowance</td><td class="text-end">{{ number_format($payroll->medical_allowance, 2) }}</td></tr>
                        <tr><td>Special Allowance</td><td class="text-end">{{ number_format($payroll->special_allowance, 2) }}</td></tr>
                        <tr><td>Overtime Earnings</td><td class="text-end">{{ number_format($payroll->ot_amount, 2) }}</td></tr>
                        @foreach($payroll->allowances as $a)
                        <tr><td>{{ $a->name }}</td><td class="text-end">{{ number_format($a->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="table-light fw-bold"><td>Gross Earnings</td><td class="text-end">{{ number_format($payroll->gross_earnings, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <thead class="table-danger"><tr><th>Deductions</th><th class="text-end">Amount (₹)</th></tr></thead>
                    <tbody>
                        <tr><td>LOP Deduction</td><td class="text-end">{{ number_format($payroll->lop_deduction, 2) }}</td></tr>
                        <tr><td>Employee PF</td><td class="text-end">{{ number_format($payroll->pf_employee, 2) }}</td></tr>
                        <tr><td>Employee ESI</td><td class="text-end">{{ number_format($payroll->esi_employee, 2) }}</td></tr>
                        <tr><td>TDS</td><td class="text-end">{{ number_format($payroll->tds, 2) }}</td></tr>
                        <tr><td>Advance Deduction</td><td class="text-end">{{ number_format($payroll->advance_deduction, 2) }}</td></tr>
                        @foreach($payroll->deductions as $d)
                        <tr><td>{{ $d->name }}</td><td class="text-end">{{ number_format($d->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="table-light fw-bold"><td>Total Deductions</td><td class="text-end">{{ number_format($payroll->total_deductions, 2) }}</td></tr>
                    </tbody>
                </table>
                @if($showEmployerContribution)
                <table class="table table-bordered table-sm">
                    <thead class="table-secondary"><tr><th>Employer Contributions</th><th class="text-end">Amount (₹)</th></tr></thead>
                    <tbody>
                        <tr><td>Employer PF</td><td class="text-end">{{ number_format($payroll->pf_employer, 2) }}</td></tr>
                        <tr><td>Employer ESI</td><td class="text-end">{{ number_format($payroll->esi_employer, 2) }}</td></tr>
                        <tr class="table-light fw-bold"><td>Total Employer Cost</td><td class="text-end">{{ number_format($payroll->employer_cost, 2) }}</td></tr>
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 offset-md-6">
                <table class="table table-bordered table-sm">
                    <tr class="table-primary fw-bold"><td>NET PAY</td><td class="text-end fs-5">₹{{ number_format($payroll->net_salary, 2) }}</td></tr>
                </table>
                <p class="text-muted small text-end">Amount in words: <em>{{ \App\Support\NumberToWords::convert((float) $payroll->net_salary) }}</em></p>
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
