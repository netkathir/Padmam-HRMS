@extends('layouts.app')
@section('title','Edit Department')
@section('page-title','Edit Department')
@section('page-subtitle',$department->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.departments.update', $department) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" {{ $lockedBranchId ? 'disabled' : '' }} {{ $lockedBranchId ? '' : 'required' }}>
                        <option value="">Select Branch</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) old('branch_id', $lockedBranchId ?? $department->branch_id) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @if ($lockedBranchId)
                        <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                    @endif
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $department->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $department->code) }}">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.departments.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
