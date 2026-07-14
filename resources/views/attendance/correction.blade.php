@extends('layouts.app')
@section('title', 'Attendance Correction')
@section('page-title', 'Attendance Correction')
@section('page-subtitle', 'Look up an employee and date to correct attendance')
@section('page-actions')
    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Register</a>
@endsection
@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Select…</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->employee_code }} — {{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Attendance Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Look Up</button>
            </div>
        </form>
    </div>
</div>

@if ($employee)
<div class="card">
    <div class="card-body">
        <h6 class="mb-3">{{ $employee->full_name }} ({{ $employee->employee_code }}) — {{ \Carbon\Carbon::parse(request('date'))->format('d M Y, l') }}</h6>
        <form action="{{ route('attendance.correction.post') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <input type="hidden" name="attendance_date" value="{{ request('date') }}">

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Existing In Time</label>
                    <input type="text" class="form-control" value="{{ optional($attendance?->in_time)->format('H:i') ?? '—' }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Existing Out Time</label>
                    <input type="text" class="form-control" value="{{ optional($attendance?->out_time)->format('H:i') ?? '—' }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Corrected In Time</label>
                    <input type="time" name="corrected_in_time" class="form-control @error('corrected_in_time') is-invalid @enderror" value="{{ old('corrected_in_time', optional($attendance?->in_time)->format('H:i')) }}">
                    @error('corrected_in_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Corrected Out Time</label>
                    <input type="time" name="corrected_out_time" class="form-control @error('corrected_out_time') is-invalid @enderror" value="{{ old('corrected_out_time', optional($attendance?->out_time)->format('H:i')) }}">
                    @error('corrected_out_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">If the shift crosses midnight and the out time is earlier than the in time, it's treated as the next day.</div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Attendance Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach (['present','absent','half_day','paid_leave','unpaid_leave','weekly_off','paid_holiday','unpaid_holiday','on_duty','missing_punch','pending_review'] as $st)
                            <option value="{{ $st }}" {{ old('status', $attendance?->status) === $st ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4" id="leave_type_wrap">
                    <label class="form-label">Leave Type</label>
                    <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror">
                        <option value="">Select…</option>
                        @foreach ($leaveTypes as $lt)
                            <option value="{{ $lt->id }}" {{ old('leave_type_id', $attendance?->leave_type_id) == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                        @endforeach
                    </select>
                    @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if ($canSetOvertime)
                <div class="col-md-4">
                    <label class="form-label">Overtime Hours</label>
                    <input type="number" step="0.25" min="0" name="ot_hours" class="form-control" value="{{ old('ot_hours', $attendance?->ot_hours) }}">
                </div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">Correction Reason <span class="text-danger">*</span></label>
                <textarea name="correction_reason" rows="2" class="form-control @error('correction_reason') is-invalid @enderror" required>{{ old('correction_reason') }}</textarea>
                @error('correction_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Supporting Document</label>
                <input type="file" name="supporting_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            @if ($attendance?->approver)
                <div class="alert alert-light border small">Approved By: {{ $attendance->approver->name }}</div>
            @endif

            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Correction</button>
        </form>
    </div>
</div>
@endif
@endsection
@push('scripts')
<script>
    const statusSelect = document.getElementById('status');
    const leaveWrap = document.getElementById('leave_type_wrap');
    function toggleLeaveType() {
        const needsLeave = ['paid_leave', 'unpaid_leave'].includes(statusSelect.value);
        leaveWrap.style.display = needsLeave ? '' : 'none';
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', toggleLeaveType);
        toggleLeaveType();
    }
</script>
@endpush
