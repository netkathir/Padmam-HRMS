@extends('layouts.app')
@section('title','Leave Report')
@section('page-title','Leave Report')
@section('page-subtitle','Leave utilisation and trends')
@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', now()->startOfYear()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Leave Type</label>
                <select name="leave_type_id" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending"   {{ request('status')=='pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="approved"  {{ request('status')=='approved'  ? 'selected' : '' }}>Approved</option>
                    <option value="rejected"  {{ request('status')=='rejected'  ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status')=='cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                <a href="{{ route('reports.leave', array_merge(request()->query(), ['export' => 1])) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i></a>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Employee</th><th>Department</th><th>Leave Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th>Applied</th></tr></thead>
                <tbody>
                    @forelse($leaves as $leave)
                    <tr>
                        <td>
                            <strong>{{ $leave->employee->employee_code }}</strong><br>
                            <small>{{ $leave->employee->full_name ?? '—' }}</small>
                        </td>
                        <td><small>{{ $leave->employee->department->name ?? '—' }}</small></td>
                        <td>{{ $leave->leaveType->name ?? '—' }}</td>
                        <td>{{ optional($leave->start_date)->format('d M Y') ?? '—' }}</td>
                        <td>{{ optional($leave->end_date)->format('d M Y') ?? '—' }}</td>
                        <td class="text-center">{{ $leave->total_days }}</td>
                        <td>
                            @php $sc = match($leave->status){'approved'=>'success','rejected'=>'danger','cancelled'=>'secondary',default=>'warning'}; @endphp
                            <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($leave->status) }}</span>
                        </td>
                        <td><small>{{ $leave->created_at->format('d M Y') }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No leave records for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="d-flex justify-content-end mt-2">{{ $leaves->withQueryString()->links() }}</div>
@endsection
