@extends('layouts.app')
@section('title','Edit Shift')
@section('page-title','Edit Shift')
@section('page-subtitle',$shift->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.shifts.update', $shift) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $shift->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $shift->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Work Hours</label>
                    <input type="number" step="0.5" name="work_hours" class="form-control" value="{{ old('work_hours', $shift->work_hours) }}" min="0" max="24">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $shift->start_time) }}" required>
                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $shift->end_time) }}" required>
                    @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Break (minutes)</label>
                    <input type="number" name="break_minutes" class="form-control" value="{{ old('break_minutes', $shift->break_minutes) }}" min="0" max="480">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grace (minutes)</label>
                    <input type="number" name="grace_minutes" class="form-control" value="{{ old('grace_minutes', $shift->grace_minutes) }}" min="0" max="120">
                </div>
                <div class="col-12 d-flex gap-4">
                    <div class="form-check">
                        <input type="hidden" name="is_overnight" value="0">
                        <input type="checkbox" name="is_overnight" class="form-check-input" value="1" {{ old('is_overnight', $shift->is_overnight) ? 'checked' : '' }}>
                        <label class="form-check-label">Overnight Shift</label>
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $shift->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.shifts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
