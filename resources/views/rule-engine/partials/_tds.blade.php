@php
    $d = $rule->tdsRule ?? null;
    $selectedTaxable = old('taxable_components', $d->taxable_components ?? []);
    $selectedExempt = old('exempt_components', $d->exempt_components ?? []);
@endphp
<div class="rule-category-fields" data-category="tds" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">TDS Rule</h6></div>
        <div class="col-md-3">
            <div class="form-check mt-2">
                <input type="hidden" name="tds_applicable" value="0">
                <input type="checkbox" name="tds_applicable" class="form-check-input" value="1" {{ old('tds_applicable', $d->tds_applicable ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">TDS Applicable</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Salary Slab From <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="salary_slab_from" class="form-control" value="{{ old('salary_slab_from', $d->salary_slab_from ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Salary Slab To <span class="text-muted">(optional upper limit)</span></label>
            <input type="number" step="0.01" name="salary_slab_to" class="form-control" value="{{ old('salary_slab_to', $d->salary_slab_to ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">TDS Percentage <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="tds_percentage" class="form-control" value="{{ old('tds_percentage', $d->tds_percentage ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Calculation Basis <span class="text-danger">*</span></label>
            <select name="calculation_basis" class="form-select">
                @foreach(['monthly_gross'=>'Monthly Gross','annual_estimated_income'=>'Annual Estimated Income','taxable_income'=>'Taxable Income'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('calculation_basis', $d->calculation_basis ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Rounding Method <span class="text-danger">*</span></label>
            <select name="rounding_method" class="form-select">
                @foreach(['nearest'=>'Nearest','up'=>'Up','down'=>'Down'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('rounding_method', $d->rounding_method ?? 'nearest') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <div class="form-check mt-4">
                <input type="hidden" name="fixed_tds_amount_allowed" value="0">
                <input type="checkbox" name="fixed_tds_amount_allowed" class="form-check-input" value="1" {{ old('fixed_tds_amount_allowed', $d->fixed_tds_amount_allowed ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">Fixed TDS Amount Allowed <span class="text-muted">(permission-controlled)</span></label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Taxable Components <span class="text-danger">*</span></label>
            <div class="border rounded p-2" style="max-height:120px;overflow-y:auto;">
                @foreach($earningsComponents as $c)
                <div class="form-check">
                    <input type="checkbox" name="taxable_components[]" class="form-check-input" id="taxc_{{ $c->id }}" value="{{ $c->id }}" {{ in_array((string)$c->id, array_map('strval', $selectedTaxable)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="taxc_{{ $c->id }}">{{ $c->name }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Exempt Components</label>
            <div class="border rounded p-2" style="max-height:120px;overflow-y:auto;">
                @foreach($earningsComponents as $c)
                <div class="form-check">
                    <input type="checkbox" name="exempt_components[]" class="form-check-input" id="exc_{{ $c->id }}" value="{{ $c->id }}" {{ in_array((string)$c->id, array_map('strval', $selectedExempt)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="exc_{{ $c->id }}">{{ $c->name }}</label>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
