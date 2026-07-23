@extends('layouts.app')
@section('title','Edit Leave Type')
@section('page-title','Edit Leave Type')
@section('page-subtitle',$leaveType->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.leave-types.update', $leaveType) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Leave Type Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $leaveType->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Leave Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $leaveType->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Days Per Year <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="days_per_year" class="form-control @error('days_per_year') is-invalid @enderror" value="{{ old('days_per_year', $leaveType->days_per_year) }}" min="0" max="365" required>
                    @error('days_per_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $leaveType->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Status (Active) <span class="text-danger">*</span></label>
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" class="form-check-input" value="1" {{ old('is_paid', $leaveType->is_paid) ? 'checked' : '' }}>
                        <label class="form-check-label">Paid Leave <span class="text-danger">*</span></label>
                    </div>
                    @error('is_paid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Employee Type Applicability <span class="text-danger">*</span></label>
                    {{-- See masters/shifts/edit.blade.php for why hasOldInput() is required here:
                         unchecking every box submits no applicable_employee_types[] key at all, and a
                         naive old(..., $leaveType->...) fallback would wrongly re-check every box from
                         the database instead of showing that nothing was actually submitted. --}}
                    @php
                        $selectedTypes = session()->hasOldInput()
                            ? old('applicable_employee_types', [])
                            : ($leaveType->applicable_employee_types ?? ['staff','company_labour','contract_labour']);
                    @endphp
                    <div class="border rounded p-2 @error('applicable_employee_types') is-invalid @enderror">
                        @foreach(config('employee_types') as $val=>$label)
                        <div class="form-check">
                            <input type="checkbox" name="applicable_employee_types[]" class="form-check-input" id="etype_{{ $val }}" value="{{ $val }}" {{ in_array($val, $selectedTypes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="etype_{{ $val }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                    @error('applicable_employee_types')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.leave-types.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
