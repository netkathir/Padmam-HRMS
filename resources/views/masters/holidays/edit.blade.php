@extends('layouts.app')
@section('title','Edit Holiday')
@section('page-title','Edit Holiday')
@section('page-subtitle',$holiday->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.holidays.update', $holiday) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Calendar Name <span class="text-danger">*</span></label>
                    <input type="text" name="calendar_name" class="form-control @error('calendar_name') is-invalid @enderror" value="{{ old('calendar_name', $holiday->calendar_name) }}" required>
                    @error('calendar_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Holiday Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $holiday->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $holiday->date->format('Y-m-d')) }}" required>
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="public_holiday"   {{ old('type', $holiday->type) == 'public_holiday'   ? 'selected' : '' }}>Public Holiday</option>
                        <option value="festival_holiday" {{ old('type', $holiday->type) == 'festival_holiday' ? 'selected' : '' }}>Festival Holiday</option>
                        <option value="optional"         {{ old('type', $holiday->type) == 'optional'         ? 'selected' : '' }}>Optional Holiday</option>
                        <option value="company_holiday"  {{ old('type', $holiday->type) == 'company_holiday'  ? 'selected' : '' }}>Company Holiday</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch <span class="text-danger">*</span> {{ $lockedBranchId ? '' : '(or All Branches)' }}</label>
                    <select name="branch_id" class="form-select" {{ $lockedBranchId ? 'disabled' : '' }}>
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) old('branch_id', $lockedBranchId ?? $holiday->branch_id) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @if ($lockedBranchId)
                        <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                        <div class="form-text">Locked to your assigned branch.</div>
                    @endif
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" class="form-check-input" value="1" {{ old('is_paid', $holiday->is_paid) ? 'checked' : '' }}>
                        <label class="form-check-label">Paid Holiday</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $holiday->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Applicable Employee Type <span class="text-danger">*</span></label>
                    @php $selectedTypes = old('applicable_employee_types', $holiday->applicable_employee_types ?? ['staff','company_labour','contract_labour']); @endphp
                    <div class="border rounded p-2 @error('applicable_employee_types') is-invalid @enderror">
                        @foreach(['staff'=>'Staff','company_labour'=>'Company Labour','contract_labour'=>'Contract Labour'] as $val=>$label)
                        <div class="form-check">
                            <input type="checkbox" name="applicable_employee_types[]" class="form-check-input" id="etype_{{ $val }}" value="{{ $val }}" {{ in_array($val, $selectedTypes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="etype_{{ $val }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                    @error('applicable_employee_types')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $holiday->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.holidays.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
