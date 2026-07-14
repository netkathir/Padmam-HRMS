@extends('layouts.app')
@section('title','Apply for Leave')
@section('page-title','Apply for Leave')
@section('page-subtitle','Submit a new leave request')
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('leaves.store') }}" method="POST">
                    @csrf
                    @can('leaves.full')
                    <div class="mb-3">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                            <option value="">Select employee…</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ (string) old('employee_id', $employee?->id) === (string) $emp->id ? 'selected' : '' }}>
                                {{ $emp->employee_code }} — {{ $emp->full_name ?? $emp->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @else
                    <input type="hidden" name="employee_id" value="{{ auth()->user()->employee_id }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                            <option value="">Select type…</option>
                            @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }} ({{ $type->days_per_year }} days/yr)
                            </option>
                            @endforeach
                        </select>
                        @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Days</label>
                            <input type="number" id="days_count" class="form-control" readonly placeholder="—">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_half_day" id="is_half_day" value="1" class="form-check-input" {{ old('is_half_day') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_half_day">Half Day</label>
                    </div>
                    <div class="mb-3" id="half_day_period_wrap" style="display:none">
                        <label class="form-label">Half Day Period</label>
                        <select name="half_day_period" class="form-select @error('half_day_period') is-invalid @enderror">
                            <option value="first" {{ old('half_day_period') == 'first' ? 'selected' : '' }}>First Half</option>
                            <option value="second" {{ old('half_day_period') == 'second' ? 'selected' : '' }}>Second Half</option>
                        </select>
                        @error('half_day_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit Request</button>
                        <a href="{{ route('leaves.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    const from = document.querySelector('[name="start_date"]');
    const to   = document.querySelector('[name="end_date"]');
    const days = document.getElementById('days_count');
    const halfDay = document.getElementById('is_half_day');
    const halfDayWrap = document.getElementById('half_day_period_wrap');

    function calcDays() {
        if (halfDay.checked) { days.value = 0.5; return; }
        if (from.value && to.value) {
            const diff = (new Date(to.value) - new Date(from.value)) / 86400000 + 1;
            days.value = diff > 0 ? diff : '';
        }
    }
    function toggleHalfDay() {
        halfDayWrap.style.display = halfDay.checked ? '' : 'none';
        calcDays();
    }
    from.addEventListener('change', calcDays);
    to.addEventListener('change', calcDays);
    halfDay.addEventListener('change', toggleHalfDay);
    toggleHalfDay();
</script>
@endpush
