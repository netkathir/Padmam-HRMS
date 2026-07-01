@extends('layouts.app')
@section('title', 'Contract Payroll')
@section('page-title', 'Contract Payroll')
@section('page-subtitle', 'Wage records for contract workers')

@section('page-actions')
    <a href="{{ route('contract-payroll.calculate') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-calculator"></i> Calculate Payroll
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">

        {{-- Filters --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="contractor_id" class="form-select form-select-sm">
                    <option value="">All Contractors</option>
                    @foreach($contractors as $c)
                        <option value="{{ $c->id }}" {{ request('contractor_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="month" class="form-select form-select-sm">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    @foreach(range(now()->year - 2, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Payments</option>
                    <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="paid"      {{ request('status') == 'paid'      ? 'selected' : '' }}>Paid</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>

        {{-- Summary --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="p-2 bg-light rounded text-center">
                    <small class="text-muted">Records</small>
                    <div class="fw-bold fs-5">{{ $summary->count ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-success-subtle rounded text-center">
                    <small class="text-muted">Gross Wages</small>
                    <div class="fw-bold text-success">₹{{ number_format($summary->gross ?? 0, 0) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 bg-primary-subtle rounded text-center">
                    <small class="text-muted">Net Payable</small>
                    <div class="fw-bold text-primary">₹{{ number_format($summary->net ?? 0, 0) }}</div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Contractor</th>
                        <th>Worker</th>
                        <th>Wage Type</th>
                        <th>Present / Half / Absent</th>
                        <th>OT Hrs</th>
                        <th>Total Wages</th>
                        <th>OT Amount</th>
                        <th>Net</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $pr)
                        <tr>
                            <td>{{ $records->firstItem() + $loop->index }}</td>
                            <td><small>{{ optional($pr->contractor)->name }}</small></td>
                            <td class="fw-semibold">{{ optional($pr->worker)->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $pr->wage_type === 'daily' ? 'info' : 'primary' }}-subtle text-{{ $pr->wage_type === 'daily' ? 'info' : 'primary' }}">
                                    {{ ucfirst($pr->wage_type) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-success fw-semibold">{{ (int)$pr->present_days }}P</span> /
                                <span class="text-warning">{{ (int)$pr->half_days }}H</span> /
                                <span class="text-danger">{{ (int)$pr->absent_days }}A</span>
                            </td>
                            <td>{{ $pr->ot_hours > 0 ? $pr->ot_hours . 'h' : '—' }}</td>
                            <td>₹{{ number_format($pr->total_wages, 0) }}</td>
                            <td>{{ $pr->ot_amount > 0 ? '₹' . number_format($pr->ot_amount, 0) : '—' }}</td>
                            <td class="fw-bold text-success">₹{{ number_format($pr->net_wages, 0) }}</td>
                            <td>
                                @php $pc = ['pending'=>'warning','paid'=>'success','cancelled'=>'secondary']; @endphp
                                <span class="badge bg-{{ $pc[$pr->payment_status] ?? 'secondary' }}-subtle text-{{ $pc[$pr->payment_status] ?? 'secondary' }}">
                                    {{ ucfirst($pr->payment_status) }}
                                </span>
                                @if($pr->payment_date)
                                    <br><small class="text-muted">{{ $pr->payment_date->format('d-m-Y') }}</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('contract-payroll.show', $pr->id) }}"
                                   class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">No payroll records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $records->links() }}</div>

    </div>
</div>
@endsection
