@extends('layouts.app')
@section('title','Edit Deduction Component')
@section('page-title','Edit Deduction Component')
@section('page-subtitle',$deductionsComponent->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.deductions.update', $deductionsComponent) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Component Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $deductionsComponent->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $deductionsComponent->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $deductionsComponent->sort_order) }}" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $deductionsComponent->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="fixed"      {{ old('type', $deductionsComponent->type) == 'fixed'      ? 'selected' : '' }}>Fixed</option>
                        <option value="percentage" {{ old('type', $deductionsComponent->type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                        <option value="statutory"  {{ old('type', $deductionsComponent->type) == 'statutory'  ? 'selected' : '' }}>Statutory</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Calculation Base</label>
                    <input type="text" name="calculation_base" class="form-control" value="{{ old('calculation_base', $deductionsComponent->calculation_base) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Percentage (%)</label>
                    <input type="number" step="0.01" name="percentage" class="form-control" value="{{ old('percentage', $deductionsComponent->percentage) }}" min="0" max="100">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_statutory" value="0">
                        <input type="checkbox" name="is_statutory" class="form-check-input" value="1" {{ old('is_statutory', $deductionsComponent->is_statutory) ? 'checked' : '' }}>
                        <label class="form-check-label">Statutory Deduction (PF/ESI/TDS)</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.deductions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
