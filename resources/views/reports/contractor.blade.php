@extends('layouts.app')
@section('title', 'Contractor Report')
@section('page-title', 'Contractor Report')
@section('page-subtitle', 'Summary of contractor payment status by month')

@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Reports
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">

        <form method="GET" class="row g-2 mb-4">
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
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> View</button>
            </div>
        </form>

        <h6 class="fw-semibold mb-3">
            {{ \Carbon\Carbon::create($year, $month)->format('F Y') }} — All Contractors
        </h6>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Contractor</th>
                        <th>Company</th>
                        <th>Total Workers</th>
                        <th>Workers w/ Payroll</th>
                        <th>Total Gross</th>
                        <th>Total Net</th>
                        <th>Paid</th>
                        <th>Pending</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contractors as $c)
                        @php
                            $ps      = $payrollSummary->get($c->id);
                            $pwCount = optional($ps)->worker_count ?? 0;
                            $gross   = optional($ps)->total_gross ?? 0;
                            $net     = optional($ps)->total_net ?? 0;
                            $paid    = optional($ps)->paid_count ?? 0;
                            $pending = $pwCount - $paid;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $c->name }}</td>
                            <td><small class="text-muted">{{ $c->company_name ?? '—' }}</small></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $c->contract_workers_count }}
                                </span>
                            </td>
                            <td>{{ $pwCount ?: '—' }}</td>
                            <td>{{ $gross > 0 ? '₹' . number_format($gross, 0) : '—' }}</td>
                            <td class="fw-semibold {{ $net > 0 ? 'text-success' : '' }}">
                                {{ $net > 0 ? '₹' . number_format($net, 0) : '—' }}
                            </td>
                            <td>
                                @if($paid > 0)
                                    <span class="badge bg-success-subtle text-success">{{ $paid }} paid</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($pending > 0)
                                    <span class="badge bg-warning-subtle text-warning">{{ $pending }} pending</span>
                                @elseif($pwCount > 0)
                                    <span class="badge bg-success-subtle text-success">All paid</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('contract-payroll.index', ['contractor_id' => $c->id, 'month' => $month, 'year' => $year]) }}"
                                   class="btn btn-sm btn-outline-info" title="View Payroll">
                                    <i class="bi bi-cash-stack"></i>
                                </a>
                                <a href="{{ route('contract-attendance.report', ['contractor_id' => $c->id, 'month' => $month, 'year' => $year]) }}"
                                   class="btn btn-sm btn-outline-success" title="Attendance Report">
                                    <i class="bi bi-calendar-check"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No contractors found.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($contractors->count() > 0 && $payrollSummary->count() > 0)
                    <tfoot>
                        <tr class="table-light fw-semibold">
                            <td colspan="5">Total</td>
                            <td>₹{{ number_format($payrollSummary->sum('total_gross'), 0) }}</td>
                            <td class="text-success">₹{{ number_format($payrollSummary->sum('total_net'), 0) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
