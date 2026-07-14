@php $d = $rule->employeeNumberRule ?? null; @endphp
<div class="rule-category-fields" data-category="employee_number" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Employee Number Rule</h6></div>
        <div class="col-md-3">
            <label class="form-label">Employee Category <span class="text-danger">*</span></label>
            <select name="employee_category" class="form-select">
                <option value="">Select</option>
                @foreach(['staff'=>'Staff','company_labour'=>'Company Labour','contract_labour'=>'Contract Labour'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('employee_category', $d->employee_category ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Branch <span class="text-muted">(for branch-wise numbering)</span></label>
            <select name="branch_id" class="form-select">
                <option value="">None</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ (string) old('branch_id', $d->branch_id ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Contractor <span class="text-muted">(for contractor-wise numbering)</span></label>
            <select name="contractor_id" class="form-select">
                <option value="">None</option>
                @foreach($contractors as $c)
                    <option value="{{ $c->id }}" {{ (string) old('contractor_id', $d->contractor_id ?? '') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Prefix <span class="text-muted">(e.g. STF, CL, CTL)</span></label>
            <input type="text" name="prefix" class="form-control" value="{{ old('prefix', $d->prefix ?? '') }}">
        </div>
        <div class="col-md-2">
            <div class="form-check mt-4">
                <input type="hidden" name="include_branch_code" value="0">
                <input type="checkbox" name="include_branch_code" class="form-check-input" value="1" {{ old('include_branch_code', $d->include_branch_code ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">Include Branch Code</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-check mt-4">
                <input type="hidden" name="include_contractor_code" value="0">
                <input type="checkbox" name="include_contractor_code" class="form-check-input" value="1" {{ old('include_contractor_code', $d->include_contractor_code ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">Include Contractor Code</label>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label">Separator</label>
            <input type="text" name="separator" class="form-control" maxlength="5" value="{{ old('separator', $d->separator ?? '-') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sequence Start <span class="text-danger">*</span></label>
            <input type="number" name="sequence_start" class="form-control" min="1" value="{{ old('sequence_start', $d->sequence_start ?? 1) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sequence Length <span class="text-danger">*</span></label>
            <input type="number" name="sequence_length" class="form-control" min="1" max="10" value="{{ old('sequence_length', $d->sequence_length ?? 4) }}">
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="include_financial_year" value="0">
                <input type="checkbox" name="include_financial_year" class="form-check-input" value="1" {{ old('include_financial_year', $d->include_financial_year ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">Financial Year Inclusion</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="include_calendar_year" value="0">
                <input type="checkbox" name="include_calendar_year" class="form-check-input" value="1" {{ old('include_calendar_year', $d->include_calendar_year ?? false) ? 'checked' : '' }}>
                <label class="form-check-label">Calendar Year Inclusion</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Reset Frequency <span class="text-danger">*</span></label>
            <select name="reset_frequency" class="form-select">
                @foreach(['never'=>'Never','yearly'=>'Yearly','financial_yearly'=>'Financial Yearly','branch_wise'=>'Branch-wise'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('reset_frequency', $d->reset_frequency ?? 'never') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="allow_manual_override" value="0">
                <input type="checkbox" name="allow_manual_override" class="form-check-input" value="1" {{ old('allow_manual_override', $d->allow_manual_override ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">Allow Manual Override <span class="text-muted">(permission-controlled)</span></label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Sample Employee Number</label>
            <input type="text" id="sample-employee-number" class="form-control" readonly placeholder="Fill fields above and click Preview">
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="preview-employee-number-btn">
                <i class="bi bi-eye"></i> Preview Sample Number
            </button>
        </div>
    </div>
</div>
