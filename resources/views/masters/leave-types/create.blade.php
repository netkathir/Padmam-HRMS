@extends('layouts.app')
@section('title','Add Leave Type')
@section('page-title','Add Leave Type')
@section('page-subtitle','Create a new leave category')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.leave-types.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Leave Type Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Days Per Year <span class="text-danger">*</span></label>
                    <input type="number" step="0.5" name="days_per_year" class="form-control @error('days_per_year') is-invalid @enderror" value="{{ old('days_per_year', 12) }}" required min="0">
                    @error('days_per_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Carry Forward</label>
                    <input type="number" step="0.5" name="max_carry_forward" class="form-control" value="{{ old('max_carry_forward', 0) }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min Notice Days</label>
                    <input type="number" name="min_notice_days" class="form-control" value="{{ old('min_notice_days', 0) }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Consecutive Days</label>
                    <input type="number" name="max_consecutive_days" class="form-control" value="{{ old('max_consecutive_days') }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Applicable Gender <span class="text-danger">*</span></label>
                    <select name="gender_specific" class="form-select @error('gender_specific') is-invalid @enderror" required>
                        <option value="all"    {{ old('gender_specific', 'all') == 'all'    ? 'selected' : '' }}>All</option>
                        <option value="male"   {{ old('gender_specific') == 'male'   ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender_specific') == 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('gender_specific')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-4">
                        @foreach(['is_paid'=>'Paid Leave','is_carry_forward'=>'Carry Forward','is_half_day_allowed'=>'Half Day Allowed','requires_document'=>'Requires Document','is_active'=>'Active'] as $field => $label)
                        <div class="form-check">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" class="form-check-input" value="1"
                                {{ old($field, in_array($field, ['is_paid','is_active']) ? '1' : '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('masters.leave-types.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
