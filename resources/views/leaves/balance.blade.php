@extends('layouts.app')
@section('title','Leave Balance')
@section('page-title','Leave Balance')
@section('page-subtitle','Leave balance for ' . $year)
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ (string) $year === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @if($departments->isNotEmpty())
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee</label>
                <input type="text" name="search" class="form-control" placeholder="Name or code…" value="{{ request('search') }}">
            </div>
            @endif
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead>
                    <tr>
                        <th>Employee Number</th>
                        <th>Employee Name</th>
                        <th>Leave Type</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Accrued</th>
                        <th class="text-end">Carry Fwd</th>
                        <th class="text-end">Adjusted</th>
                        <th class="text-end">Used</th>
                        <th class="text-end">Lapsed</th>
                        <th class="text-end">Available</th>
                        @can('leaves.full')
                        <th>Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $bal)
                    <tr>
                        <td>{{ $bal->employee->employee_code ?? '—' }}</td>
                        <td>{{ $bal->employee->full_name ?? '—' }}</td>
                        <td>{{ $bal->leaveType->name ?? '—' }}</td>
                        <td class="text-end">{{ number_format($bal->opening_balance, 1) }}</td>
                        <td class="text-end">{{ number_format($bal->allocated_days, 1) }}</td>
                        <td class="text-end">{{ number_format($bal->carry_forward_days, 1) }}</td>
                        <td class="text-end {{ $bal->adjusted_days != 0 ? ($bal->adjusted_days > 0 ? 'text-success' : 'text-danger') : '' }}">{{ number_format($bal->adjusted_days, 1) }}</td>
                        <td class="text-end">{{ number_format($bal->used_days, 1) }}</td>
                        <td class="text-end">{{ number_format($bal->lapsed_days, 1) }}</td>
                        <td class="text-end fw-bold {{ $bal->available_balance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($bal->available_balance, 1) }}</td>
                        @can('leaves.full')
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $bal->id }}">
                                <i class="bi bi-sliders"></i> Adjust
                            </button>
                        </td>
                        <div class="modal fade" id="adjustModal{{ $bal->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('leaves.balance.adjust', $bal) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h6 class="modal-title">Adjust Balance — {{ $bal->employee->full_name ?? '' }} ({{ $bal->leaveType->name ?? '' }})</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Adjustment (Days) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.5" name="adjustment_days" class="form-control" required placeholder="e.g. 2 or -1.5">
                                                <div class="form-text">Positive to add days, negative to deduct.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea name="reason" class="form-control" rows="2" required></textarea>
                                            </div>
                                            @if($bal->adjustments->isNotEmpty())
                                            <div class="small text-muted">
                                                <strong>History:</strong>
                                                <ul class="mb-0">
                                                    @foreach($bal->adjustments->take(5) as $adj)
                                                    <li>{{ $adj->adjustment_days > 0 ? '+' : '' }}{{ $adj->adjustment_days }} — {{ $adj->reason }} ({{ $adj->created_at->format('d M Y') }})</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Adjustment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endcan
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No leave balance records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
