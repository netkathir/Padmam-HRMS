@extends('layouts.app')
@section('title','Add Salary Slab')
@section('page-title','Add Salary Slab')
@section('page-subtitle','Define a new salary band')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.salary-slabs.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Slab Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Junior, Mid-Level, Senior" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min CTC (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="min_ctc" class="form-control @error('min_ctc') is-invalid @enderror" value="{{ old('min_ctc') }}" min="0" step="1000" required>
                    @error('min_ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max CTC (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="max_ctc" class="form-control @error('max_ctc') is-invalid @enderror" value="{{ old('max_ctc') }}" min="0" step="1000" required>
                    @error('max_ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active','1')=='1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('masters.salary-slabs.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
