@php
    $rule = $rule ?? null;
    $selectedBranches = old('branch_ids', $rule->branch_ids ?? []);
    $selectedTypes = old('employee_types', $rule->employee_types ?? []);
    $selectedLabourTypes = old('labour_types', $rule->labour_types ?? []);
    $selectedContractors = old('contractor_ids', $rule->contractor_ids ?? []);
@endphp
<div class="col-12"><h6 class="text-primary border-bottom pb-1">Common Rule Header</h6></div>
<div class="col-md-6">
    <label class="form-label">Rule Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $rule->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-3">
    <label class="form-label">Rule Category <span class="text-danger">*</span></label>
    <select name="category" id="rule-category" class="form-select @error('category') is-invalid @enderror" {{ $rule ? 'disabled' : '' }} required>
        <option value="">Select Category</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ old('category', $rule->category ?? '') == $cat ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ', $cat)) }}</option>
        @endforeach
    </select>
    @if($rule)
        <input type="hidden" name="category" value="{{ $rule->category }}">
        <div class="form-text">Category cannot be changed after creation.</div>
    @endif
    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-3">
    <label class="form-label">Priority <span class="text-danger">*</span></label>
    <input type="number" name="priority" class="form-control @error('priority') is-invalid @enderror" value="{{ old('priority', $rule->priority ?? 100) }}" min="1" required>
    <div class="form-text">Lower number = applied first.</div>
    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="col-md-4">
    <label class="form-label">Branch Applicability <span class="text-muted">(blank = all branches)</span></label>
    <div class="border rounded p-2" style="max-height:150px;overflow-y:auto;">
        @foreach($branches as $b)
        <div class="form-check">
            <input type="checkbox" name="branch_ids[]" class="form-check-input" id="branch_{{ $b->id }}" value="{{ $b->id }}" {{ in_array((string)$b->id, array_map('strval', $selectedBranches)) ? 'checked' : '' }}>
            <label class="form-check-label" for="branch_{{ $b->id }}">{{ $b->name }}</label>
        </div>
        @endforeach
    </div>
</div>
<div class="col-md-4">
    <label class="form-label">Employee Type <span class="text-danger">*</span></label>
    <div class="border rounded p-2 @error('employee_types') is-invalid @enderror">
        @foreach(['staff'=>'Staff','labour'=>'Labour'] as $val=>$label)
        <div class="form-check">
            <input type="checkbox" name="employee_types[]" class="form-check-input rule-employee-type" id="etype_{{ $val }}" value="{{ $val }}" {{ in_array($val, $selectedTypes) ? 'checked' : '' }}>
            <label class="form-check-label" for="etype_{{ $val }}">{{ $label }}</label>
        </div>
        @endforeach
    </div>
    @error('employee_types')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
<div class="col-md-4" id="labour-type-wrapper">
    <label class="form-label">Labour Type <span class="text-muted">(when Labour selected)</span></label>
    <div class="border rounded p-2">
        @foreach(['company_labour'=>'Company Labour','contract_labour'=>'Contract Labour'] as $val=>$label)
        <div class="form-check">
            <input type="checkbox" name="labour_types[]" class="form-check-input" id="ltype_{{ $val }}" value="{{ $val }}" {{ in_array($val, $selectedLabourTypes) ? 'checked' : '' }}>
            <label class="form-check-label" for="ltype_{{ $val }}">{{ $label }}</label>
        </div>
        @endforeach
    </div>
</div>

<div class="col-md-4">
    <label class="form-label">Contractor <span class="text-muted">(optional, contractor-specific rules)</span></label>
    <div class="border rounded p-2" style="max-height:120px;overflow-y:auto;">
        @foreach($contractors as $c)
        <div class="form-check">
            <input type="checkbox" name="contractor_ids[]" class="form-check-input" id="contractor_{{ $c->id }}" value="{{ $c->id }}" {{ in_array((string)$c->id, array_map('strval', $selectedContractors)) ? 'checked' : '' }}>
            <label class="form-check-label" for="contractor_{{ $c->id }}">{{ $c->name }}</label>
        </div>
        @endforeach
    </div>
</div>
<div class="col-md-3">
    <label class="form-label">Effective From <span class="text-danger">*</span></label>
    <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', optional($rule->effective_from ?? null)->toDateString()) }}" required>
    @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-3">
    <label class="form-label">Effective To</label>
    <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', optional($rule->effective_to ?? null)->toDateString()) }}">
    @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-2">
    <label class="form-label">Status <span class="text-danger">*</span></label>
    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
        <option value="active" {{ old('status', $rule->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $rule->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-12">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="2">{{ old('description', $rule->description ?? '') }}</textarea>
</div>
