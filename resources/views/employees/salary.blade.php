@extends('layouts.app')
@section('title','Employee Salary')
@section('page-title','Salary Details')
@section('page-subtitle',$employee->full_name ?? $employee->name)
@section('page-actions')
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Profile</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Update Salary Structure</h6></div>
            <div class="card-body">
                <form action="{{ route('employees.salary.store', $employee) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Salary Slab</label>
                        <select name="salary_slab_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($salarySlabs as $slab)
                            <option value="{{ $slab->id }}" {{ old('salary_slab_id', $employee->salary_slab_id) == $slab->id ? 'selected' : '' }}>
                                {{ $slab->name }} (₹{{ number_format($slab->min_salary) }} – ₹{{ number_format($slab->max_salary) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Basic Salary (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="basic_salary" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ old('basic_salary', $employee->basic_salary) }}" required>
                        @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HRA (₹)</label>
                        <input type="number" name="hra" step="0.01" min="0" class="form-control" value="{{ old('hra', $employee->hra) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Other Allowances (₹)</label>
                        <input type="number" name="other_allowances" step="0.01" min="0" class="form-control" value="{{ old('other_allowances', $employee->other_allowances) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', now()->format('Y-m-d')) }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="pf_applicable" value="0">
                        <input type="checkbox" name="pf_applicable" class="form-check-input" value="1" {{ old('pf_applicable', $employee->pf_applicable) ? 'checked' : '' }}>
                        <label class="form-check-label">PF Applicable</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="esi_applicable" value="0">
                        <input type="checkbox" name="esi_applicable" class="form-check-input" value="1" {{ old('esi_applicable', $employee->esi_applicable) ? 'checked' : '' }}>
                        <label class="form-check-label">ESI Applicable</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Update Salary</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Current Salary Summary</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Basic Salary</dt>
                    <dd class="col-sm-7">₹{{ number_format($employee->basic_salary ?? 0, 2) }}</dd>
                    <dt class="col-sm-5">HRA</dt>
                    <dd class="col-sm-7">₹{{ number_format($employee->hra ?? 0, 2) }}</dd>
                    <dt class="col-sm-5">Other Allowances</dt>
                    <dd class="col-sm-7">₹{{ number_format($employee->other_allowances ?? 0, 2) }}</dd>
                    <dt class="col-sm-5 fw-bold border-top pt-2">Gross CTC</dt>
                    <dd class="col-sm-7 fw-bold border-top pt-2">₹{{ number_format(($employee->basic_salary ?? 0) + ($employee->hra ?? 0) + ($employee->other_allowances ?? 0), 2) }}</dd>
                    <dt class="col-sm-5">PF</dt>
                    <dd class="col-sm-7">{{ $employee->pf_applicable ? 'Applicable' : 'Not Applicable' }}</dd>
                    <dt class="col-sm-5">ESI</dt>
                    <dd class="col-sm-7">{{ $employee->esi_applicable ? 'Applicable' : 'Not Applicable' }}</dd>
                </dl>
            </div>
        </div>
        @if(!empty($salaryHistory))
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Salary History</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Effective From</th><th>Basic</th><th>Gross</th></tr></thead>
                        <tbody>
                            @foreach($salaryHistory as $hist)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($hist->effective_from)->format('d M Y') }}</td>
                                <td>₹{{ number_format($hist->basic_salary, 0) }}</td>
                                <td>₹{{ number_format($hist->gross, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
