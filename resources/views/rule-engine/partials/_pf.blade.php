@php $d = $rule->pfRule ?? null; $selectedComponents = old('pf_wage_components', $d->pf_wage_components ?? []); @endphp
<div class="rule-category-fields" data-category="pf" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">PF Rule</h6></div>
        <div class="col-md-3">
            <div class="form-check mt-2">
                <input type="hidden" name="pf_applicable" value="0">
                <input type="checkbox" name="pf_applicable" class="form-check-input" value="1" {{ old('pf_applicable', $d->pf_applicable ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">PF Applicable</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Salary Slab From <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="salary_slab_from" class="form-control" value="{{ old('salary_slab_from', $d->salary_slab_from ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Salary Slab To</label>
            <input type="number" step="0.01" name="salary_slab_to" class="form-control" value="{{ old('salary_slab_to', $d->salary_slab_to ?? '') }}">
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
            <label class="form-label">PF Wage Components <span class="text-danger">*</span></label>
            <div class="border rounded p-2" style="max-height:120px;overflow-y:auto;">
                @foreach($earningsComponents as $c)
                <div class="form-check">
                    <input type="checkbox" name="pf_wage_components[]" class="form-check-input" id="pfwc_{{ $c->id }}" value="{{ $c->id }}" {{ in_array((string)$c->id, array_map('strval', $selectedComponents)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="pfwc_{{ $c->id }}">{{ $c->name }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label">Employee PF % <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="employee_pf_percentage" class="form-control" value="{{ old('employee_pf_percentage', $d->employee_pf_percentage ?? 12) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Employer PF % <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="employer_pf_percentage" class="form-control" value="{{ old('employer_pf_percentage', $d->employer_pf_percentage ?? 12) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">PF Wage Ceiling</label>
            <input type="number" step="0.01" name="pf_wage_ceiling" class="form-control" value="{{ old('pf_wage_ceiling', $d->pf_wage_ceiling ?? 15000) }}">
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="restrict_to_wage_ceiling" value="0">
                <input type="checkbox" name="restrict_to_wage_ceiling" class="form-check-input" value="1" {{ old('restrict_to_wage_ceiling', $d->restrict_to_wage_ceiling ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">Restrict to Wage Ceiling</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="voluntary_pf_allowed" value="0">
                <input type="checkbox" name="voluntary_pf_allowed" class="form-check-input" value="1" {{ old('voluntary_pf_allowed', $d->voluntary_pf_allowed ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">Voluntary PF Allowed</label>
            </div>
        </div>
    </div>
</div>
