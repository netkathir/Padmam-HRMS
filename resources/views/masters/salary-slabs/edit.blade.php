@extends('layouts.app')
@section('title','Edit Salary Slab')
@section('page-title','Edit Salary Slab')
@section('page-subtitle',$salarySlab->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.salary-slabs.update', $salarySlab) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Slab Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $salarySlab->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="min_salary" class="form-control @error('min_salary') is-invalid @enderror" value="{{ old('min_salary', $salarySlab->min_salary) }}" min="0" step="1000" required>
                    @error('min_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="max_salary" class="form-control @error('max_salary') is-invalid @enderror" value="{{ old('max_salary', $salarySlab->max_salary) }}" min="0" step="1000" required>
                    @error('max_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $salarySlab->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.salary-slabs.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
