@extends('layouts.app')
@section('title','Edit Earning Component')
@section('page-title','Edit Earning Component')
@section('page-subtitle',$earningsComponent->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.earnings.update', $earningsComponent) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Component Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $earningsComponent->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $earningsComponent->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $earningsComponent->sort_order) }}" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $earningsComponent->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="fixed"      {{ old('type', $earningsComponent->type) == 'fixed'      ? 'selected' : '' }}>Fixed</option>
                        <option value="percentage" {{ old('type', $earningsComponent->type) == 'percentage' ? 'selected' : '' }}>Percentage of Base</option>
                        <option value="formula"    {{ old('type', $earningsComponent->type) == 'formula'    ? 'selected' : '' }}>Formula</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Calculation Base</label>
                    <input type="text" name="calculation_base" class="form-control" value="{{ old('calculation_base', $earningsComponent->calculation_base) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Percentage (%)</label>
                    <input type="number" step="0.01" name="percentage" class="form-control" value="{{ old('percentage', $earningsComponent->percentage) }}" min="0" max="100">
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-4">
                        @foreach(['is_taxable'=>'Taxable','is_pf_applicable'=>'PF Applicable','is_esi_applicable'=>'ESI Applicable'] as $field => $label)
                        <div class="form-check">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" class="form-check-input" value="1" {{ old($field, $earningsComponent->{$field}) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.earnings.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
