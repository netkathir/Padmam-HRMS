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
                    <label class="form-label">From Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="min_ctc" class="form-control @error('min_ctc') is-invalid @enderror" value="{{ old('min_ctc', $salarySlab->min_ctc) }}" min="0" step="1000" required>
                    @error('min_ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="max_ctc" class="form-control @error('max_ctc') is-invalid @enderror" value="{{ old('max_ctc', $salarySlab->max_ctc) }}" min="0" step="1000" required>
                    @error('max_ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">TDS / PF / ESI Percentages</h6></div>
                <div class="col-md-2">
                    <label class="form-label">TDS %</label>
                    <input type="number" step="0.01" name="tds_percentage" class="form-control @error('tds_percentage') is-invalid @enderror" value="{{ old('tds_percentage', $salarySlab->tds_percentage) }}" min="0" max="100">
                    @error('tds_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">PF Employee %</label>
                    <input type="number" step="0.01" name="pf_employee_percentage" class="form-control @error('pf_employee_percentage') is-invalid @enderror" value="{{ old('pf_employee_percentage', $salarySlab->pf_employee_percentage) }}" min="0" max="100">
                    @error('pf_employee_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">PF Employer %</label>
                    <input type="number" step="0.01" name="pf_employer_percentage" class="form-control @error('pf_employer_percentage') is-invalid @enderror" value="{{ old('pf_employer_percentage', $salarySlab->pf_employer_percentage) }}" min="0" max="100">
                    @error('pf_employer_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">ESI Employee %</label>
                    <input type="number" step="0.01" name="esi_employee_percentage" class="form-control @error('esi_employee_percentage') is-invalid @enderror" value="{{ old('esi_employee_percentage', $salarySlab->esi_employee_percentage) }}" min="0" max="100">
                    @error('esi_employee_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">ESI Employer %</label>
                    <input type="number" step="0.01" name="esi_employer_percentage" class="form-control @error('esi_employer_percentage') is-invalid @enderror" value="{{ old('esi_employer_percentage', $salarySlab->esi_employer_percentage) }}" min="0" max="100">
                    @error('esi_employer_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Applicability & Effective Period</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Branch {{ '(or All Branches)' }}</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) old('branch_id', $salarySlab->branch_id) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
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
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $salarySlab->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Earnings / Deductions Composition</h6></div>
                <div class="col-12" id="components-wrapper">
                    <table class="table table-sm" id="components-table">
                        <thead><tr><th>Type</th><th>Component</th><th>Value Type</th><th>Value</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="add-component-row"><i class="bi bi-plus-lg"></i> Add Component</button>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.salary-slabs.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@php
    $existingComponents = $salarySlab->components->map(fn($c) => [
        'component_type' => $c->component_type,
        'component_id'   => $c->component_id,
        'value_type'      => $c->value_type,
        'value'           => $c->value,
    ])->values();
@endphp
<script>
(function() {
    const earnings   = @json($earnings->map(fn($e) => ['id'=>$e->id,'name'=>$e->name]));
    const deductions = @json($deductions->map(fn($d) => ['id'=>$d->id,'name'=>$d->name]));
    const existing   = @json($existingComponents);
    let rowIndex = 0;

    function addRow(values) {
        values = values || {};
        const tbody = document.querySelector('#components-table tbody');
        const i = rowIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="components[${i}][component_type]" class="form-select form-select-sm component-type">
                    <option value="earning" ${values.component_type==='earning'?'selected':''}>Earning</option>
                    <option value="deduction" ${values.component_type==='deduction'?'selected':''}>Deduction</option>
                </select>
            </td>
            <td><select name="components[${i}][component_id]" class="form-select form-select-sm component-id"></select></td>
            <td>
                <select name="components[${i}][value_type]" class="form-select form-select-sm">
                    <option value="percentage" ${values.value_type==='percentage'?'selected':''}>Percentage</option>
                    <option value="fixed" ${values.value_type==='fixed'?'selected':''}>Fixed</option>
                </select>
            </td>
            <td><input type="number" step="0.01" min="0" name="components[${i}][value]" class="form-control form-control-sm" value="${values.value ?? ''}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
        populateComponentOptions(tr.querySelector('.component-type'), tr.querySelector('.component-id'), values.component_id);
    }

    function populateComponentOptions(typeSelect, idSelect, selectedId) {
        const list = typeSelect.value === 'earning' ? earnings : deductions;
        idSelect.innerHTML = list.map(c => `<option value="${c.id}" ${String(c.id)===String(selectedId)?'selected':''}>${c.name}</option>`).join('');
    }

    document.getElementById('add-component-row').addEventListener('click', () => addRow());
    document.getElementById('components-table').addEventListener('change', (e) => {
        if (e.target.classList.contains('component-type')) {
            populateComponentOptions(e.target, e.target.closest('tr').querySelector('.component-id'));
        }
    });
    document.getElementById('components-table').addEventListener('click', (e) => {
        if (e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
        }
    });

    existing.forEach(addRow);
})();
</script>
@endsection
