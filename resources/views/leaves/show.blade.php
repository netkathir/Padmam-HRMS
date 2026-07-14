@extends('layouts.app')
@section('title','Leave Request Details')
@section('page-title','Leave Request')
@section('page-subtitle','#' . $leave->id)
@section('page-actions')
    <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Request Details</h6>
                @php
                    $statusColor = match($leave->status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'secondary',
                        default     => 'warning'
                    };
                @endphp
                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} fs-6">{{ ucfirst($leave->status) }}</span>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Employee</dt>
                    <dd class="col-sm-8">{{ $leave->employee->full_name ?? '—' }} ({{ $leave->employee->employee_code ?? '' }})</dd>

                    <dt class="col-sm-4">Leave Type</dt>
                    <dd class="col-sm-8">{{ $leave->leaveType->name ?? '—' }}</dd>

                    <dt class="col-sm-4">From Date</dt>
                    <dd class="col-sm-8">{{ $leave->start_date?->format('d M Y, l') }}</dd>

                    <dt class="col-sm-4">To Date</dt>
                    <dd class="col-sm-8">{{ $leave->end_date?->format('d M Y, l') }}</dd>

                    <dt class="col-sm-4">Total Days</dt>
                    <dd class="col-sm-8"><span class="badge bg-primary-subtle text-primary fs-6">{{ $leave->total_days ?? '—' }}</span>
                        @if($leave->is_half_day)
                            <span class="badge bg-secondary-subtle text-secondary">Half Day ({{ ucfirst($leave->half_day_period) }})</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Reason</dt>
                    <dd class="col-sm-8">{{ $leave->reason }}</dd>

                    <dt class="col-sm-4">Applied On</dt>
                    <dd class="col-sm-8">{{ $leave->created_at->format('d M Y H:i') }}</dd>

                    @if($leave->approved_by)
                    <dt class="col-sm-4">{{ $leave->status == 'approved' ? 'Approved' : 'Rejected' }} By</dt>
                    <dd class="col-sm-8">{{ $leave->approver->name ?? '—' }} on {{ $leave->updated_at->format('d M Y H:i') }}</dd>
                    @endif

                    @if($leave->rejection_reason)
                    <dt class="col-sm-4">Rejection Reason</dt>
                    <dd class="col-sm-8 text-danger">{{ $leave->rejection_reason }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @if($leave->status === 'pending' && auth()->user()->can('leaves.full'))
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Action</h6></div>
            <div class="card-body">
                <form action="{{ route('leaves.approve', $leave) }}" method="POST" class="mb-2">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-success w-100"><i class="bi bi-check-lg"></i> Approve</button>
                </form>
                <form action="{{ route('leaves.approve', $leave) }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <div class="mb-2">
                        <textarea name="rejection_reason" class="form-control form-control-sm" rows="2" placeholder="Reason for rejection…"></textarea>
                    </div>
                    <button class="btn btn-danger w-100"><i class="bi bi-x-lg"></i> Reject</button>
                </form>
            </div>
        </div>
        @endif
        @if($leave->status === 'pending' && auth()->user()->employee_id === $leave->employee_id)
        <div class="card">
            <div class="card-body">
                <form action="{{ route('leaves.cancel', $leave) }}" method="POST" onsubmit="return confirm('Cancel this leave request?')">
                    @csrf
                    <button class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle"></i> Cancel Request</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
