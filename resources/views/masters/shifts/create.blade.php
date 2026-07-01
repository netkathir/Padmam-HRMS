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
                <div class="col-md-6">
                    <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <label class="form-label">Break (minutes)</label>
                    <input type="number" name="break_minutes" class="form-control" value="{{ old('break_minutes', 30) }}" min="0" max="480">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grace (minutes)</label>
                    <input type="number" name="grace_minutes" class="form-control" value="{{ old('grace_minutes', 10) }}" min="0" max="120">
                </div>
                <div class="col-12 d-flex gap-4">
                    <div class="form-check">
                        <input type="hidden" name="is_overnight" value="0">
                        <input type="checkbox" name="is_overnight" class="form-check-input" value="1" {{ old('is_overnight') ? 'checked' : '' }}>
                        <label class="form-check-label">Overnight Shift</label>
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('masters.shifts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
