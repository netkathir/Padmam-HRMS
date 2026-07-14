@extends('layouts.app')
@section('title', 'Attendance Details')
@section('page-title', 'Attendance Details')
@section('page-subtitle', $attendance->employee->full_name . ' — ' . $attendance->date->format('d M Y'))
@section('page-actions')
    <a href="{{ route('attendance.correction.form', ['employee_id' => $attendance->employee_id, 'date' => $attendance->date->toDateString()]) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Correct</a>
    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Register</a>
@endsection
@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Summary</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Employee</dt><dd class="col-sm-8">{{ $attendance->employee->full_name }} ({{ $attendance->employee->employee_code }})</dd>
                    <dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $attendance->employee->department->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Shift</dt><dd class="col-sm-8">{{ $attendance->shift->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Date</dt><dd class="col-sm-8">{{ $attendance->date->format('d M Y, l') }}</dd>
                    <dt class="col-sm-4">In Time</dt><dd class="col-sm-8">{{ optional($attendance->in_time)->format('d M Y H:i') ?? '—' }}</dd>
                    <dt class="col-sm-4">Out Time</dt><dd class="col-sm-8">{{ optional($attendance->out_time)->format('d M Y H:i') ?? '—' }}</dd>
                    <dt class="col-sm-4">Total Hours</dt><dd class="col-sm-8">{{ $attendance->work_hours }}</dd>
                    <dt class="col-sm-4">Late / Early Exit</dt><dd class="col-sm-8">{{ $attendance->late_minutes }} min / {{ $attendance->early_exit_minutes }} min</dd>
                    <dt class="col-sm-4">Overtime</dt><dd class="col-sm-8">{{ $attendance->ot_hours }} hrs @if($attendance->ot_minutes > 0)<span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($attendance->ot_approval_status ?? 'pending') }}</span>@endif</dd>
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge bg-secondary-subtle text-secondary">{{ $attendance->status_label }}</span></dd>
                    <dt class="col-sm-4">Leave Type</dt><dd class="col-sm-8">{{ $attendance->leaveType->name ?? '—' }}</dd>
                    <dt class="col-sm-4">LOP Days</dt><dd class="col-sm-8">{{ $attendance->lop_days ?? '—' }}</dd>
                    <dt class="col-sm-4">Source</dt><dd class="col-sm-8">{{ $attendance->source_label }}</dd>
                    <dt class="col-sm-4">Remarks</dt><dd class="col-sm-8">{{ $attendance->remarks ?? '—' }}</dd>
                    @if ($attendance->correction_reason)
                    <dt class="col-sm-4">Correction Reason</dt><dd class="col-sm-8">{{ $attendance->correction_reason }}</dd>
                    @endif
                    @if ($attendance->supporting_document_path)
                    <dt class="col-sm-4">Supporting Document</dt><dd class="col-sm-8"><a href="{{ Storage::url($attendance->supporting_document_path) }}" target="_blank">View</a></dd>
                    @endif
                    @if ($attendance->approver)
                    <dt class="col-sm-4">Approved By</dt><dd class="col-sm-8">{{ $attendance->approver->name }} on {{ optional($attendance->approved_at)->format('d M Y H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Raw Punches</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Time</th><th>Type</th><th>Source</th></tr></thead>
                    <tbody>
                        @forelse ($attendance->logs as $log)
                        <tr><td>{{ $log->punch_time->format('H:i:s') }}</td><td>{{ ucfirst($log->punch_type) }}</td><td>{{ ucfirst($log->source) }}</td></tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No raw punches linked.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
