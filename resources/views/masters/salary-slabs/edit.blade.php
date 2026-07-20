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

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">TDS / PF / ESI Percentages</h6></div>
                <div class="col-md-2">
                    <label class="form-label">TDS % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="tds_percentage" class="form-control @error('tds_percentage') is-invalid @enderror" value="{{ old('tds_percentage', $salarySlab->tds_percentage) }}" min="0" max="100" required>
                    @error('tds_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">PF Employee % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="pf_employee_percentage" class="form-control @error('pf_employee_percentage') is-invalid @enderror" value="{{ old('pf_employee_percentage', $salarySlab->pf_employee_percentage) }}" min="0" max="100" required>
                    @error('pf_employee_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">PF Employer % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="pf_employer_percentage" class="form-control @error('pf_employer_percentage') is-invalid @enderror" value="{{ old('pf_employer_percentage', $salarySlab->pf_employer_percentage) }}" min="0" max="100" required>
                    @error('pf_employer_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">ESI Employee % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="esi_employee_percentage" class="form-control @error('esi_employee_percentage') is-invalid @enderror" value="{{ old('esi_employee_percentage', $salarySlab->esi_employee_percentage) }}" min="0" max="100" required>
                    @error('esi_employee_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">ESI Employer % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="esi_employer_percentage" class="form-control @error('esi_employer_percentage') is-invalid @enderror" value="{{ old('esi_employer_percentage', $salarySlab->esi_employer_percentage) }}" min="0" max="100" required>
                    @error('esi_employer_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 mt-2">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $salarySlab->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Status (Active) <span class="text-danger">*</span></label>
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
