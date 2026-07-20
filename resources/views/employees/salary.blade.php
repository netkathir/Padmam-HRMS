@extends('layouts.app')
@section('title','Employee Salary')
@section('page-title','Salary Details')
@section('page-subtitle',$employee->full_name ?? $employee->name)
@section('page-actions')
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Profile</a>
@endsection
@section('content')
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
                            @foreach($slabs as $slab)
                            <option value="{{ $slab->id }}" {{ old('salary_slab_id', $salary->salary_slab_id ?? null) == $slab->id ? 'selected' : '' }}>
                                {{ $slab->name }} (₹{{ number_format($slab->min_ctc) }} – ₹{{ number_format($slab->max_ctc) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CTC (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="ctc" step="0.01" min="0" class="form-control @error('ctc') is-invalid @enderror" value="{{ old('ctc', $salary->ctc ?? '') }}" required>
                        @error('ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Basic Salary (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="basic_salary" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ old('basic_salary', $salary->basic_salary ?? '') }}" required>
                        @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">HRA (₹)</label>
                            <input type="number" name="hra" step="0.01" min="0" class="form-control" value="{{ old('hra', $salary->hra ?? 0) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">DA (₹)</label>
                            <input type="number" name="da" step="0.01" min="0" class="form-control" value="{{ old('da', $salary->da ?? 0) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">TA (₹)</label>
                            <input type="number" name="ta" step="0.01" min="0" class="form-control" value="{{ old('ta', $salary->ta ?? 0) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Medical Allowance (₹)</label>
                            <input type="number" name="medical_allowance" step="0.01" min="0" class="form-control" value="{{ old('medical_allowance', $salary->medical_allowance ?? 0) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Special Allowance (₹)</label>
                            <input type="number" name="special_allowance" step="0.01" min="0" class="form-control" value="{{ old('special_allowance', $salary->special_allowance ?? 0) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', now()->format('Y-m-d')) }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="pf_applicable" value="0">
                        <input type="checkbox" name="pf_applicable" class="form-check-input" value="1" {{ old('pf_applicable', $employee->is_pf_applicable) ? 'checked' : '' }}>
                        <label class="form-check-label">PF Applicable</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="hidden" name="esi_applicable" value="0">
                        <input type="checkbox" name="esi_applicable" class="form-check-input" value="1" {{ old('esi_applicable', $employee->is_esi_applicable) ? 'checked' : '' }}>
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
                @if($salary)
                <dl class="row mb-0">
                    <dt class="col-sm-5">Basic Salary</dt>
                    <dd class="col-sm-7">₹{{ number_format($salary->basic_salary, 2) }}</dd>
                    <dt class="col-sm-5">HRA</dt>
                    <dd class="col-sm-7">₹{{ number_format($salary->hra, 2) }}</dd>
                    <dt class="col-sm-5">DA</dt>
                    <dd class="col-sm-7">₹{{ number_format($salary->da, 2) }}</dd>
                    <dt class="col-sm-5">TA</dt>
                    <dd class="col-sm-7">₹{{ number_format($salary->ta, 2) }}</dd>
                    <dt class="col-sm-5">Medical Allowance</dt>
                    <dd class="col-sm-7">₹{{ number_format($salary->medical_allowance, 2) }}</dd>
                    <dt class="col-sm-5">Special Allowance</dt>
                    <dd class="col-sm-7">₹{{ number_format($salary->special_allowance, 2) }}</dd>
                    <dt class="col-sm-5 fw-bold border-top pt-2">Gross Salary</dt>
                    <dd class="col-sm-7 fw-bold border-top pt-2">₹{{ number_format($salary->gross_salary, 2) }}</dd>
                    <dt class="col-sm-5">CTC</dt>
                    <dd class="col-sm-7">₹{{ number_format($salary->ctc, 2) }}</dd>
                    <dt class="col-sm-5">PF</dt>
                    <dd class="col-sm-7">{{ $employee->is_pf_applicable ? 'Applicable' : 'Not Applicable' }}</dd>
                    <dt class="col-sm-5">ESI</dt>
                    <dd class="col-sm-7">{{ $employee->is_esi_applicable ? 'Applicable' : 'Not Applicable' }}</dd>
                </dl>
                @else
                <p class="text-muted mb-0">No salary structure recorded yet.</p>
                @endif
            </div>
        </div>
        @if($history->isNotEmpty())
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Salary History</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Effective From</th><th>Effective To</th><th>Basic</th><th>Gross</th></tr></thead>
                        <tbody>
                            @foreach($history as $hist)
                            <tr>
                                <td>{{ $hist->effective_from?->format('d M Y') }}</td>
                                <td>{{ $hist->effective_to?->format('d M Y') ?? ($hist->is_current ? 'Current' : '—') }}</td>
                                <td>₹{{ number_format($hist->basic_salary, 0) }}</td>
                                <td>₹{{ number_format($hist->gross_salary, 0) }}</td>
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
