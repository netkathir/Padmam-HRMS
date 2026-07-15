@extends('layouts.app')
@section('title','Edit Salary Slab')
@section('page-title','Edit Salary Slab')
@section('page-subtitle',$salarySlab->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.salary-slabs.update', $salarySlab) }}" method="POST" id="salarySlabForm">
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
                    <input type="number" step="0.01" id="tds_percentage" name="tds_percentage" class="form-control @error('tds_percentage') is-invalid @enderror" value="{{ old('tds_percentage', $salarySlab->tds_percentage) }}" min="0" max="100" required>
                    @error('tds_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">PF Employee % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="pf_employee_percentage" name="pf_employee_percentage" class="form-control @error('pf_employee_percentage') is-invalid @enderror" value="{{ old('pf_employee_percentage', $salarySlab->pf_employee_percentage) }}" min="0" max="100" required>
                    @error('pf_employee_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">PF Employer % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="pf_employer_percentage" name="pf_employer_percentage" class="form-control @error('pf_employer_percentage') is-invalid @enderror" value="{{ old('pf_employer_percentage', $salarySlab->pf_employer_percentage) }}" min="0" max="100" required>
                    @error('pf_employer_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">ESI Employee % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="esi_employee_percentage" name="esi_employee_percentage" class="form-control @error('esi_employee_percentage') is-invalid @enderror" value="{{ old('esi_employee_percentage', $salarySlab->esi_employee_percentage) }}" min="0" max="100" required>
                    @error('esi_employee_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">ESI Employer % <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="esi_employer_percentage" name="esi_employer_percentage" class="form-control @error('esi_employer_percentage') is-invalid @enderror" value="{{ old('esi_employer_percentage', $salarySlab->esi_employer_percentage) }}" min="0" max="100" required>
                    @error('esi_employer_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Salary Structure</h6></div>
                <div class="col-md-3">
                    <label class="form-label">Basic Salary (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" id="basic_salary" name="basic_salary" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ old('basic_salary', $salarySlab->basic_salary) }}" required>
                    @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                    <h6 class="fw-bold border-bottom pb-2 mb-0 flex-grow-1">Salary Components</h6>
                    <button type="button" id="addSalaryComponentRow" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Add Component
                    </button>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-sm" id="salaryComponentsTable">
                            <thead>
                                <tr><th>Salary Component</th><th>Component Type</th><th>Calculation Type</th><th>Calculation Base</th><th>Amount / Percentage</th><th>Calculated Amount</th><th></th></tr>
                            </thead>
                            <tbody id="salaryComponentsBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12 mt-2">
                    <div class="card bg-body-tertiary">
                        <div class="card-body">
                            <h6 class="mb-3">Computed Summary <small class="text-muted fw-normal">(read-only — this is what every employee on this slab will inherit)</small></h6>
                            <div class="row g-2">
                                <div class="col-md-3"><div class="text-muted small">Gross Salary</div><div class="fw-semibold" id="summary_gross">₹0.00</div></div>
                                <div class="col-md-3"><div class="text-muted small">Employer Contributions</div><div class="fw-semibold" id="summary_employer">₹0.00</div></div>
                                <div class="col-md-3"><div class="text-muted small">Total Deductions</div><div class="fw-semibold" id="summary_deductions">₹0.00</div></div>
                                <div class="col-md-3"><div class="text-muted small">Net Salary</div><div class="fw-semibold" id="summary_net">₹0.00</div></div>
                                <div class="col-md-3"><div class="text-muted small">CTC</div><div class="fw-semibold" id="summary_ctc">₹0.00</div></div>
                            </div>
                        </div>
                    </div>
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
@endsection

@php
    $wizardEarningsComponents = $earningsComponents->map(fn ($c) => [
        'id' => $c->id, 'name' => $c->name, 'type' => $c->type,
        'calculation_base' => $c->calculation_base, 'percentage' => $c->percentage,
    ]);
    $wizardDeductionsComponents = $deductionsComponents->map(fn ($c) => [
        'id' => $c->id, 'name' => $c->name, 'type' => $c->type,
        'calculation_base' => $c->calculation_base, 'percentage' => $c->percentage,
    ]);
    $wizardExistingComponents = $salarySlab->components->map(fn ($c) => [
        'component_type' => $c->component_type, 'component_id' => $c->component_id,
    ]);
@endphp
<script>
    window.__salarySlabData = {
        earningsComponents: @json($wizardEarningsComponents),
        deductionsComponents: @json($wizardDeductionsComponents),
        existingComponents: @json($wizardExistingComponents),
    };
</script>
<script>
(function () {
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

    // ── Salary Components add-row — combined searchable Earning/Deduction
    // picker, mirroring the Employee Designation & Salary tab's own picker. ──
    var basicInput = document.getElementById('basic_salary');
    var maxCtcInput = document.getElementById('max_ctc');
    var componentsBody = document.getElementById('salaryComponentsBody');
    var addComponentBtn = document.getElementById('addSalaryComponentRow');
    var componentRowIndex = 0;

    function combinedComponentOptions() {
        var earnings = window.__salarySlabData.earningsComponents
            .map(function (c) { return '<option value="' + c.id + '" data-kind="earning" data-type="' + c.type + '" data-base="' + (c.calculation_base || '') + '" data-rate="' + c.percentage + '">' + c.name + '</option>'; }).join('');
        var deductions = window.__salarySlabData.deductionsComponents
            .map(function (c) { return '<option value="' + c.id + '" data-kind="deduction" data-type="' + c.type + '" data-base="' + (c.calculation_base || '') + '" data-rate="' + c.percentage + '">' + c.name + '</option>'; }).join('');
        return '<option value="">Select…</option>'
            + (earnings ? '<optgroup label="Earnings">' + earnings + '</optgroup>' : '')
            + (deductions ? '<optgroup label="Deductions">' + deductions + '</optgroup>' : '');
    }
    function computeAmount(rate, calcType, calcBase) {
        var ctc = parseFloat(maxCtcInput.value) || 0;
        var basic = parseFloat(basicInput.value) || 0;
        if (calcType === 'percentage') {
            var base = (calcBase || '').toLowerCase().indexOf('ctc') !== -1 ? ctc : basic;
            return Math.round(base * rate / 100 * 100) / 100;
        }
        return rate;
    }
    function refreshSummary() {
        var basic = parseFloat(basicInput.value) || 0;
        var earningTotal = 0, deductionTotal = 0;
        Array.from(document.querySelectorAll('#salaryComponentsBody tr')).forEach(function (tr) {
            var amt = parseFloat(tr.dataset.amount) || 0;
            if (tr.dataset.kind === 'earning') earningTotal += amt;
            if (tr.dataset.kind === 'deduction') deductionTotal += amt;
        });
        var gross = basic + earningTotal;
        var pfEmployee = basic * (parseFloat(document.getElementById('pf_employee_percentage').value) || 0) / 100;
        var pfEmployer = basic * (parseFloat(document.getElementById('pf_employer_percentage').value) || 0) / 100;
        var esiEmployee = gross * (parseFloat(document.getElementById('esi_employee_percentage').value) || 0) / 100;
        var esiEmployer = gross * (parseFloat(document.getElementById('esi_employer_percentage').value) || 0) / 100;
        var tds = gross * (parseFloat(document.getElementById('tds_percentage').value) || 0) / 100;
        var employerContrib = pfEmployer + esiEmployer;
        var totalDeductions = deductionTotal + pfEmployee + esiEmployee + tds;
        var net = gross - totalDeductions;
        var ctc = gross + employerContrib;
        document.getElementById('summary_gross').textContent = '₹' + gross.toFixed(2);
        document.getElementById('summary_employer').textContent = '₹' + employerContrib.toFixed(2);
        document.getElementById('summary_deductions').textContent = '₹' + totalDeductions.toFixed(2);
        document.getElementById('summary_net').textContent = '₹' + net.toFixed(2);
        document.getElementById('summary_ctc').textContent = '₹' + ctc.toFixed(2);
    }

    function addComponentRow(preset) {
        var idx = componentRowIndex++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select name="components[' + idx + '][component_id]" class="form-select form-select-sm component-select"></select>' +
            '<input type="hidden" name="components[' + idx + '][component_type]" class="component-type-hidden"></td>' +
            '<td class="component-type-display text-muted">—</td>' +
            '<td class="component-calc-type text-muted">—</td>' +
            '<td class="component-calc-base text-muted">—</td>' +
            '<td class="component-rate text-muted">—</td>' +
            '<td class="component-calc-amount text-muted">—</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-component-row"><i class="bi bi-x"></i></button></td>';
        componentsBody.appendChild(tr);

        var select = tr.querySelector('.component-select');
        var typeHidden = tr.querySelector('.component-type-hidden');
        var typeDisplay = tr.querySelector('.component-type-display');
        var calcTypeCell = tr.querySelector('.component-calc-type');
        var calcBaseCell = tr.querySelector('.component-calc-base');
        var rateCell = tr.querySelector('.component-rate');
        var calcAmountCell = tr.querySelector('.component-calc-amount');

        select.innerHTML = combinedComponentOptions();

        function refreshPreview() {
            var opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) {
                typeHidden.value = ''; tr.dataset.kind = ''; tr.dataset.amount = '0';
                typeDisplay.textContent = '—'; calcTypeCell.textContent = '—'; calcBaseCell.textContent = '—'; rateCell.textContent = '—'; calcAmountCell.textContent = '—';
                refreshSummary();
                return;
            }
            var kind = opt.dataset.kind, calcType = opt.dataset.type, calcBase = opt.dataset.base, rate = parseFloat(opt.dataset.rate) || 0;
            var amount = computeAmount(rate, calcType, calcBase);
            typeHidden.value = kind; tr.dataset.kind = kind; tr.dataset.amount = amount;
            typeDisplay.textContent = kind.charAt(0).toUpperCase() + kind.slice(1);
            calcTypeCell.textContent = calcType ? (calcType.charAt(0).toUpperCase() + calcType.slice(1)) : '—';
            calcBaseCell.textContent = calcType === 'percentage' ? (calcBase || '—') : '—';
            rateCell.textContent = calcType === 'percentage' ? (rate + '%') : ('₹' + rate.toFixed(2));
            calcAmountCell.textContent = '₹' + amount.toFixed(2);
            refreshSummary();
        }

        select.addEventListener('change', refreshPreview);
        tr.querySelector('.remove-component-row').addEventListener('click', function () { tr.remove(); refreshSummary(); });

        if (preset && preset.component_id) select.value = preset.component_id;
        refreshPreview();
    }

    if (addComponentBtn) addComponentBtn.addEventListener('click', function () { addComponentRow(null); });
    (window.__salarySlabData.existingComponents || []).forEach(function (c) { addComponentRow(c); });

    ['basic_salary', 'max_ctc', 'tds_percentage', 'pf_employee_percentage', 'pf_employer_percentage', 'esi_employee_percentage', 'esi_employer_percentage'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', refreshSummary);
    });
    refreshSummary();
})();
</script>
