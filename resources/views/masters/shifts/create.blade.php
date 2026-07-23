@extends('layouts.app')
@section('title','Add Shift')
@section('page-title','Add Shift')
@section('page-subtitle','Create a new work shift')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.shifts.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                @include('partials._locked_branch_field', ['currentBranch' => $currentBranch])
                <div class="col-md-6">
                    <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Shift Code is generated automatically on save.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Work Hours</label>
                    <input type="number" step="0.5" name="work_hours" class="form-control" value="{{ old('work_hours', 8) }}" min="0" max="24">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', '09:00') }}" required>
                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', '18:00') }}" required>
                    @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grace – Late Entry (minutes)</label>
                    <input type="number" name="grace_late_entry_minutes" class="form-control @error('grace_late_entry_minutes') is-invalid @enderror" value="{{ old('grace_late_entry_minutes', 10) }}" min="0">
                    @error('grace_late_entry_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grace – Early Exit (minutes)</label>
                    <input type="number" name="grace_early_exit_minutes" class="form-control @error('grace_early_exit_minutes') is-invalid @enderror" value="{{ old('grace_early_exit_minutes', 10) }}" min="0">
                    @error('grace_early_exit_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-flex gap-4">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label">Active <span class="text-danger">*</span></label>
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Employee Type Applicability <span class="text-danger">*</span></label>
                    <div class="border rounded p-2 @error('applicable_employee_types') is-invalid @enderror">
                        @foreach(config('employee_types') as $val=>$label)
                        <div class="form-check">
                            <input type="checkbox" name="applicable_employee_types[]" class="form-check-input" id="etype_{{ $val }}" value="{{ $val }}" {{ in_array($val, old('applicable_employee_types', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="etype_{{ $val }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                    @error('applicable_employee_types')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit</button>
                <a href="{{ route('masters.shifts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
