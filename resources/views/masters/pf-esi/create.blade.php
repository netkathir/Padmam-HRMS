@extends('layouts.app')
@section('title','Add PF/ESI Config')
@section('page-title','Add PF & ESI Configuration')
@section('page-subtitle','Set statutory contribution rates')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.pf-esi.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from') }}" required>
                    @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active','1')=='1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Provident Fund (PF)</h6></div>
                <div class="col-md-3">
                    <label class="form-label">Employee Contribution % <span class="text-danger">*</span></label>
                    <input type="number" name="pf_employee_pct" step="0.01" min="0" max="100" class="form-control @error('pf_employee_pct') is-invalid @enderror" value="{{ old('pf_employee_pct', 12) }}" required>
                    @error('pf_employee_pct')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employer Contribution % <span class="text-danger">*</span></label>
                    <input type="number" name="pf_employer_pct" step="0.01" min="0" max="100" class="form-control @error('pf_employer_pct') is-invalid @enderror" value="{{ old('pf_employer_pct', 12) }}" required>
                    @error('pf_employer_pct')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Wage Ceiling (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="pf_wage_ceiling" min="0" step="100" class="form-control @error('pf_wage_ceiling') is-invalid @enderror" value="{{ old('pf_wage_ceiling', 15000) }}" required>
                    @error('pf_wage_ceiling')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Employee State Insurance (ESI)</h6></div>
                <div class="col-md-3">
                    <label class="form-label">Employee Contribution % <span class="text-danger">*</span></label>
                    <input type="number" name="esi_employee_pct" step="0.01" min="0" max="100" class="form-control @error('esi_employee_pct') is-invalid @enderror" value="{{ old('esi_employee_pct', 0.75) }}" required>
                    @error('esi_employee_pct')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employer Contribution % <span class="text-danger">*</span></label>
                    <input type="number" name="esi_employer_pct" step="0.01" min="0" max="100" class="form-control @error('esi_employer_pct') is-invalid @enderror" value="{{ old('esi_employer_pct', 3.25) }}" required>
                    @error('esi_employer_pct')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Wage Ceiling (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="esi_wage_ceiling" min="0" step="100" class="form-control @error('esi_wage_ceiling') is-invalid @enderror" value="{{ old('esi_wage_ceiling', 21000) }}" required>
                    @error('esi_wage_ceiling')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('masters.pf-esi.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
