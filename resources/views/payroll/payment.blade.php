@extends('layouts.app')
@section('title','Salary Payment')
@section('page-title','Mark Salary Payment')
@section('page-subtitle',\Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') . ' — ' . ($payroll->employee->full_name ?? 'Employee'))
@section('page-actions')
    <a href="{{ route('payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Payroll</a>
@endsection
@section('content')
<div class="row g-3 justify-content-center">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Payroll Summary</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Employee</dt>
                    <dd class="col-sm-7">{{ $payroll->employee->full_name ?? '—' }} ({{ $payroll->employee->employee_code ?? '' }})</dd>
                    <dt class="col-sm-5">Pay Period</dt>
                    <dd class="col-sm-7">{{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}</dd>
                    <dt class="col-sm-5">Gross Earnings</dt>
                    <dd class="col-sm-7">₹{{ number_format($payroll->gross_earnings, 2) }}</dd>
                    <dt class="col-sm-5">Total Deductions</dt>
                    <dd class="col-sm-7 text-danger">₹{{ number_format($payroll->total_deductions, 2) }}</dd>
                    <dt class="col-sm-5 fw-bold border-top pt-2">Net Pay</dt>
                    <dd class="col-sm-7 fw-bold border-top pt-2 text-success fs-5">₹{{ number_format($payroll->net_salary, 2) }}</dd>
                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7">
                        @php $sc = match($payroll->status){'paid'=>'success','closed'=>'dark','confirmed'=>'info',default=>'warning'}; @endphp
                        <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ $payroll->status_label }}</span>
                        @if($payroll->status === 'paid')<span class="badge bg-success-subtle text-success">Paid</span>@endif
                    </dd>
                </dl>
            </div>
        </div>
        @if($payroll->status === 'paid')
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            Salary already marked as paid{{ $payroll->payments->last() ? ' on ' . $payroll->payments->last()->payment_date->format('d M Y') : '' }}.
            <a href="{{ route('payroll.payslip', $payroll) }}" class="alert-link ms-2">View Payslip</a>
        </div>
        @elseif($payroll->status !== 'closed')
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            This payroll must be Confirmed and Closed before payment can be recorded. Current status: <strong>{{ $payroll->status_label }}</strong>.
        </div>
        @else
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Record Payment</h6></div>
            <div class="card-body">
                <form action="{{ route('payroll.payment.store', $payroll) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror" required>
                                <option value="bank_transfer" {{ old('payment_mode')=='bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="cash" {{ old('payment_mode')=='cash' ? 'selected' : '' }}>Cash</option>
                                <option value="cheque" {{ old('payment_mode')=='cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="upi" {{ old('payment_mode')=='upi' ? 'selected' : '' }}>UPI</option>
                            </select>
                            @error('payment_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $payroll->net_salary) }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference / UTR Number</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="Transaction ID or cheque number">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="bi bi-cash-coin"></i> Mark as Paid</button>
                        <a href="{{ route('payroll.payslip', $payroll) }}" class="btn btn-outline-info"><i class="bi bi-file-earmark-text"></i> View Payslip</a>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
