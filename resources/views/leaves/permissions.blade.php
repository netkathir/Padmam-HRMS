@extends('layouts.app')
@section('title','Permission Leaves')
@section('page-title','Permission Leaves')
@section('page-subtitle','Short-duration permission requests (less than a day)')
@section('page-actions')
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPermissionModal"><i class="bi bi-plus-lg"></i> Request Permission</button>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month', now()->format('Y-m')) }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending"  {{ request('status')=='pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status')=='rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Employee</th><th>Date</th><th>From</th><th>To</th><th>Duration</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($permissions as $perm)
                    <tr>
                        <td>
                            <strong>{{ $perm->employee->employee_code }}</strong><br>
                            <small class="text-muted">{{ $perm->employee->full_name ?? '—' }}</small>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($perm->date)->format('d M Y') }}</td>
                        <td>{{ $perm->from_time }}</td>
                        <td>{{ $perm->to_time }}</td>
                        <td><span class="badge bg-info-subtle text-info">{{ $perm->duration_hours ?? '—' }}h</span></td>
                        <td><small>{{ Str::limit($perm->reason, 35) }}</small></td>
                        <td>
                            @php $sc = match($perm->status){ 'approved'=>'success','rejected'=>'danger',default=>'warning' }; @endphp
                            <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($perm->status) }}</span>
                        </td>
                        <td>
                            @if($perm->status === 'pending' && auth()->user()->can('leaves.full'))
                            <form action="{{ route('leaves.approve', $perm) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                            </form>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No permission requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $permissions->links() }}</div>
    </div>
</div>

<div class="modal fade" id="addPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Request Permission Leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('leaves.permissions.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @can('leaves.full')
                    <div class="mb-3">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select…</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->employee_code }} — {{ $emp->full_name ?? $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="employee_id" value="{{ auth()->user()->employee_id }}">
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">From Time <span class="text-danger">*</span></label>
                            <input type="time" name="from_time" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">To Time <span class="text-danger">*</span></label>
                            <input type="time" name="to_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
