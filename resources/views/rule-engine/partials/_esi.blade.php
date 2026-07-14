@php $d = $rule->esiRule ?? null; $selectedComponents = old('esi_wage_components', $d->esi_wage_components ?? []); @endphp
<div class="rule-category-fields" data-category="esi" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">ESI Rule</h6></div>
        <div class="col-md-3">
            <div class="form-check mt-2">
                <input type="hidden" name="esi_applicable" value="0">
                <input type="checkbox" name="esi_applicable" class="form-check-input" value="1" {{ old('esi_applicable', $d->esi_applicable ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">ESI Applicable</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Salary Slab From <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="salary_slab_from" class="form-control" value="{{ old('salary_slab_from', $d->salary_slab_from ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Salary Slab To <span class="text-muted">(wage limit)</span></label>
            <input type="number" step="0.01" name="salary_slab_to" class="form-control" value="{{ old('salary_slab_to', $d->salary_slab_to ?? 21000) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Rounding Method <span class="text-danger">*</span></label>
            <select name="rounding_method" class="form-select">
                @foreach(['nearest'=>'Nearest','up'=>'Up','down'=>'Down'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('rounding_method', $d->rounding_method ?? 'nearest') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">ESI Wage Components <span class="text-danger">*</span></label>
            <div class="border rounded p-2" style="max-height:120px;overflow-y:auto;">
                @foreach($earningsComponents as $c)
                <div class="form-check">
                    <input type="checkbox" name="esi_wage_components[]" class="form-check-input" id="esiwc_{{ $c->id }}" value="{{ $c->id }}" {{ in_array((string)$c->id, array_map('strval', $selectedComponents)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="esiwc_{{ $c->id }}">{{ $c->name }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Employee ESI % <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="employee_esi_percentage" class="form-control" value="{{ old('employee_esi_percentage', $d->employee_esi_percentage ?? 0.75) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Employer ESI % <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="employer_esi_percentage" class="form-control" value="{{ old('employer_esi_percentage', $d->employer_esi_percentage ?? 3.25) }}">
        </div>
    </div>
</div>
