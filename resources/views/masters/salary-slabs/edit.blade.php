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
                    <label class="form-label">Slab Name</label>
                    <input type="text" id="slabNamePreview" class="form-control" value="{{ $salarySlab->name }}" disabled>
                    <div class="form-text">Generated automatically from From Salary / To Salary.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" id="min_ctc" name="min_ctc" class="form-control @error('min_ctc') is-invalid @enderror" value="{{ old('min_ctc', $salarySlab->min_ctc) }}" min="0" step="1000" required>
                    @error('min_ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" id="max_ctc" name="max_ctc" class="form-control @error('max_ctc') is-invalid @enderror" value="{{ old('max_ctc', $salarySlab->max_ctc) }}" min="0" step="1000" required>
                    @error('max_ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Applicability & Effective Period</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', optional($salarySlab->effective_from)->toDateString()) }}" required>
                    @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', optional($salarySlab->effective_to)->toDateString()) }}">
                    @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $salarySlab->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Status (Active) <span class="text-danger">*</span></label>
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Employee Type Applicability <span class="text-danger">*</span></label>
                    @php $selectedTypes = old('applicable_employee_types', $salarySlab->applicable_employee_types ?? ['staff','company_labour','contract_labour']); @endphp
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
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.salary-slabs.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
(function() {
    var minInput = document.getElementById('min_ctc');
    var maxInput = document.getElementById('max_ctc');
    var preview = document.getElementById('slabNamePreview');

    function updatePreview() {
        var min = parseFloat(minInput.value) || 0;
        var max = parseFloat(maxInput.value) || 0;
        preview.value = '₹' + min.toLocaleString('en-IN') + ' - ₹' + max.toLocaleString('en-IN');
    }

    minInput.addEventListener('input', updatePreview);
    maxInput.addEventListener('input', updatePreview);
})();
</script>
@endsection
