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
                @if($currentBranch)
                    <input type="hidden" name="branch_id" value="{{ $currentBranch->id }}">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="{{ $currentBranch->name }}" disabled>
                    </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">Slab Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $salarySlab->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Salary From <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="salary_from" class="form-control @error('salary_from') is-invalid @enderror" value="{{ old('salary_from', $salarySlab->salary_from) }}" min="0" required>
                    @error('salary_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Salary To <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="salary_to" class="form-control @error('salary_to') is-invalid @enderror" value="{{ old('salary_to', $salarySlab->salary_to) }}" min="0" required>
                    @error('salary_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Earnings (% of Gross Salary)</h6></div>
                <div class="col-12">
                    @php
                        $existingEarnings = old('earnings', $salarySlab->earningsComponents->map(fn($c) => ['component_id' => $c->component_id, 'value' => $c->rate])->values()->all());
                        if (empty($existingEarnings)) { $existingEarnings = ['']; }
                    @endphp
                    <div id="earnings-rows">
                        @foreach($existingEarnings as $i => $row)
                        <div class="row g-2 mb-2 earnings-row">
                            <div class="col-md-6">
                                <select name="earnings[{{ $i }}][component_id]" class="form-select">
                                    <option value="">Select Earning</option>
                                    @foreach($earningsComponents as $ec)
                                        <option value="{{ $ec->id }}" {{ (string) ($row['component_id'] ?? '') === (string) $ec->id ? 'selected' : '' }}>{{ $ec->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="number" step="0.01" name="earnings[{{ $i }}][value]" class="form-control" placeholder="%" min="0" max="100" value="{{ $row['value'] ?? '' }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger w-100 remove-earning-row"><i class="bi bi-dash-lg"></i></button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-earning-row"><i class="bi bi-plus-lg"></i> Add Earning</button>
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
                <div class="col-md-2">
                    <label class="form-label">LOP % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="lop_percentage" class="form-control @error('lop_percentage') is-invalid @enderror" value="{{ old('lop_percentage', $salarySlab->lop_percentage) }}" min="0" max="100" required>
                    <div class="form-text">% of the standard LOP deduction to apply — 100 = full deduction.</div>
                    @error('lop_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

<template id="earning-row-template">
    <div class="row g-2 mb-2 earnings-row">
        <div class="col-md-6">
            <select name="earnings[__INDEX__][component_id]" class="form-select">
                <option value="">Select Earning</option>
                @foreach($earningsComponents as $ec)
                    <option value="{{ $ec->id }}">{{ $ec->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <input type="number" step="0.01" name="earnings[__INDEX__][value]" class="form-control" placeholder="%" min="0" max="100">
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger w-100 remove-earning-row"><i class="bi bi-dash-lg"></i></button>
        </div>
    </div>
</template>

<script>
(function() {
    const container = document.getElementById('earnings-rows');
    const template = document.getElementById('earning-row-template');
    let index = {{ count($existingEarnings) }};

    document.getElementById('add-earning-row').addEventListener('click', function() {
        const html = template.innerHTML.replaceAll('__INDEX__', index++);
        container.insertAdjacentHTML('beforeend', html);
    });

    container.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-earning-row');
        if (btn) {
            btn.closest('.earnings-row').remove();
        }
    });
})();
</script>
@endsection
