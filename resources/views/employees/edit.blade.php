@extends('layouts.app')

@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')
@section('page-subtitle', $employee->full_name)

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($employee->is_draft)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> This employee is still a draft. Complete all tabs and click "Save Employee" on the final tab to finish registration.</div>
    @endif
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto overflow-y-hidden" id="employeeWizardNav">
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="1">1. Employee Classification</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="2">2. Personal Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="3">3. Contact Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="4">4. Address Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="5">5. Employment Information</button></li>
                <li class="nav-item" id="nav-tab-6-item"><button type="button" class="nav-link" data-nav-tab="6">6. Contract Labour Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="7">7. Statutory Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="8">8. Bank Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="9">9. Salary Structure</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="10">10. Employee Documents</button></li>
            </ul>

            <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data" id="employeeWizardForm">
                @csrf @method('PUT')
                @include('employees._tabs_1_7', ['employee' => $employee])
            </form>

            {{-- ── Tab 8: Bank Information ─────────────────────────────── --}}
            <div class="tab-pane" data-tab-pane="8">
                @if ($employee->bankDetails->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Mode</th><th>Bank</th><th>Account Holder</th><th>Account Number</th><th>IFSC</th><th>Primary</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($employee->bankDetails as $bd)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', $bd->payment_mode)) }}</td>
                                        <td>{{ $bd->bank->name ?? $bd->bank_name ?? '—' }}</td>
                                        <td>{{ $bd->account_holder_name ?? '—' }}</td>
                                        <td>{{ $canViewFullBankDetails ? $bd->account_number : $bd->masked_account_number }}</td>
                                        <td>{{ $bd->ifsc_code ?? '—' }}</td>
                                        <td>{{ $bd->is_primary ? 'Yes' : '' }}</td>
                                        <td>
                                            @can('employees.full')
                                            <form action="{{ route('employees.bank-details.destroy', [$employee, $bd]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove these bank details?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No bank details on record yet.</p>
                @endif

                <form action="{{ route('employees.bank-details.store', $employee) }}" method="POST" id="bankDetailForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" id="payment_mode" class="form-select">
                                <option value="">— Not Set —</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bank Name</label>
                            <select name="bank_id" class="form-select" data-searchable>
                                <option value="">Select</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Not listed? Type it in "Other Bank Name" instead.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Other Bank Name (if not listed above)</label>
                            <input type="text" name="bank_name" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="account-holder-label">Account Holder Name</label>
                            <input type="text" name="account_holder_name" class="form-control bank-transfer-field">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" id="account-number-label">Account Number</label>
                            <div class="input-group">
                                <input type="password" name="account_number" class="form-control bank-transfer-field masked-toggle-field" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm Account Number</label>
                            <div class="input-group">
                                <input type="password" name="account_number_confirmation" class="form-control bank-transfer-field masked-toggle-field" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" id="ifsc-label">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-control bank-transfer-field" style="text-transform:uppercase">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank Branch</label>
                            <input type="text" name="branch_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Account Type</label>
                            <select name="account_type" class="form-select">
                                <option value="">Select</option>
                                <option value="savings">Savings</option>
                                <option value="current">Current</option>
                                <option value="salary">Salary</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_primary" value="1" class="form-check-input">
                                <label class="form-check-label">Set as Primary</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                        <button type="submit" name="next_tab" value="9" class="btn btn-primary">Save &amp; Next</button>
                        <button type="submit" class="btn btn-outline-primary">Save as Draft</button>
                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            {{-- ── Tab 9: Salary Structure ─────────────────────────────── --}}
            <div class="tab-pane" data-tab-pane="9">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Current Salary Summary</h6></div>
                            <div class="card-body">
                                @if($employee->currentSalary)
                                    @php $salary = $employee->currentSalary; @endphp
                                    <dl class="row mb-0">
                                        <dt class="col-sm-3">Basic</dt><dd class="col-sm-3">₹{{ number_format($salary->basic_salary, 2) }}</dd>
                                        <dt class="col-sm-3">Gross</dt><dd class="col-sm-3 fw-bold">₹{{ number_format($salary->gross_salary, 2) }}</dd>
                                        <dt class="col-sm-3">CTC</dt><dd class="col-sm-3">₹{{ number_format($salary->ctc, 2) }}</dd>
                                        <dt class="col-sm-3">Net Salary</dt><dd class="col-sm-3 fw-bold">₹{{ number_format($salary->net_salary, 2) }}</dd>
                                    </dl>
                                @else
                                    <p class="text-muted mb-0">No salary structure recorded yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($salaryHistory->isNotEmpty())
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Salary History</h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Effective From</th><th>Effective To</th><th>Basic</th><th>Gross</th></tr></thead>
                                        <tbody>
                                            @foreach($salaryHistory as $hist)
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
                    </div>
                    @endif
                </div>

                <form action="{{ route('employees.salary.store', $employee) }}" method="POST" id="salaryForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Salary Slab</label>
                            <select name="salary_slab_id" id="salary_slab_id" class="form-select" data-searchable>
                                <option value="">— None —</option>
                                @foreach($salarySlabs as $slab)
                                <option value="{{ $slab->id }}" data-tds-percentage="{{ $slab->tds_percentage }}" {{ ($employee->currentSalary?->salary_slab_id ?? null) == $slab->id ? 'selected' : '' }}>
                                    {{ $slab->name }} (₹{{ number_format($slab->min_ctc) }} – ₹{{ number_format($slab->max_ctc) }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CTC (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="ctc" id="salary_ctc" step="0.01" min="0" class="form-control @error('ctc') is-invalid @enderror" value="{{ old('ctc', $employee->currentSalary?->ctc ?? '') }}" required>
                            @error('ctc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Basic Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="basic_salary" id="salary_basic" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror" value="{{ old('basic_salary', $employee->currentSalary?->basic_salary ?? '') }}" required>
                            @error('basic_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">HRA (₹)</label>
                            <input type="number" name="hra" id="salary_hra" step="0.01" min="0" class="form-control" value="{{ old('hra', $employee->currentSalary?->hra ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">DA (₹)</label>
                            <input type="number" name="da" id="salary_da" step="0.01" min="0" class="form-control" value="{{ old('da', $employee->currentSalary?->da ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TA (₹)</label>
                            <input type="number" name="ta" id="salary_ta" step="0.01" min="0" class="form-control" value="{{ old('ta', $employee->currentSalary?->ta ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Medical Allowance (₹)</label>
                            <input type="number" name="medical_allowance" id="salary_medical" step="0.01" min="0" class="form-control" value="{{ old('medical_allowance', $employee->currentSalary?->medical_allowance ?? 0) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Special Allowance (₹)</label>
                            <input type="number" name="special_allowance" id="salary_special" step="0.01" min="0" class="form-control" value="{{ old('special_allowance', $employee->currentSalary?->special_allowance ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Effective From <span class="text-danger">*</span></label>
                            <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', now()->format('Y-m-d')) }}" required>
                            @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}">
                            @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Leave blank for an open-ended (current) structure.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gross Salary (₹)</label>
                            <input type="text" id="salary_gross_display" class="form-control" value="{{ $employee->currentSalary ? number_format($employee->currentSalary->gross_salary, 2) : '' }}" disabled>
                            <div class="form-text">Auto-calculated from Basic + Allowances + Earning Components.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="{{ $employee->currentSalary && $employee->currentSalary->is_current ? 'Current' : 'Will become Current on Save' }}" disabled>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold border-bottom pb-2 mt-2">Statutory Applicability</h6>
                            <p class="text-muted small mb-0">
                                PF: <strong id="salary_pf_display">{{ $employee->is_pf_applicable ? 'Applicable' : 'Not Applicable' }}</strong> ·
                                ESI: <strong id="salary_esi_display">{{ $employee->is_esi_applicable ? 'Applicable' : 'Not Applicable' }}</strong> ·
                                TDS: <strong id="salary_tds_display">{{ $employee->is_tds_applicable ? 'Applicable' : 'Not Applicable' }}</strong>
                                — set on Tab 7 (Statutory Information); auto-suggested here from the selected Salary Slab and Basic/Gross vs. the Statutory Configuration wage ceilings.
                            </p>
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center mt-3">
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
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                        <button type="submit" name="next_tab" value="10" class="btn btn-primary">Save &amp; Next</button>
                        <button type="submit" class="btn btn-outline-primary">Save as Draft</button>
                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            {{-- ── Tab 10: Employee Documents ──────────────────────────── --}}
            <div class="tab-pane" data-tab-pane="10">
                @if(!empty($missingMandatoryDocs))
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Missing mandatory document(s):
                        {{ collect($missingMandatoryDocs)->map(fn($t) => ucfirst(str_replace('_',' ',$t)))->implode(', ') }}
                    </div>
                @endif

                @if ($employee->documents->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-hover">
                            <thead><tr><th>Type</th><th>Number</th><th>Issued</th><th>Expiry</th><th>Remarks</th><th>Uploaded</th><th>Actions</th></tr></thead>
                            <tbody>
                                @foreach($employee->documents as $doc)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</td>
                                    <td>{{ $doc->document_number ?? '—' }}</td>
                                    <td>{{ $doc->issue_date?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if($doc->expiry_date)
                                            @php $exp = \Carbon\Carbon::parse($doc->expiry_date); @endphp
                                            <span class="{{ $exp->isPast() ? 'text-danger' : ($exp->between(now(), now()->addDays(30)) ? 'text-warning' : '') }}">{{ $exp->format('d M Y') }}</span>
                                        @else — @endif
                                    </td>
                                    <td>{{ $doc->remarks ?? '—' }}</td>
                                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                        @can('employees.full')
                                        <form action="{{ route('employees.documents.destroy', [$employee, $doc]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this document?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form action="{{ route('employees.documents.upload', $employee) }}" method="POST" enctype="multipart/form-data" id="documentsForm">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Add Document(s)</h6>
                        <button type="button" id="addDocumentRow" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i> Add Document</button>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm" id="documentsTable">
                            <thead><tr><th>Document Type</th><th>Document Number</th><th>Issue Date</th><th>Expiry Date</th><th>File</th><th>Remarks</th><th></th></tr></thead>
                            <tbody id="documentsBody"></tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
                </form>

                <div class="mt-4 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                    <form action="{{ route('employees.finalize', $employee) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Save Employee</button>
                    </form>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    // @json() splits its argument on every top-level comma (to support an
    // optional second "encoding options" argument), so a multi-key array
    // literal breaks it — each payload is precomputed into its own variable
    // first and @json() is only ever given a single bare variable here.
    $wizardEarningsComponents = $earningsComponents->map(fn ($c) => [
        'id' => $c->id, 'name' => $c->name, 'type' => $c->type,
        'calculation_base' => $c->calculation_base, 'percentage' => $c->percentage,
    ]);
    $wizardDeductionsComponents = $deductionsComponents->map(fn ($c) => [
        'id' => $c->id, 'name' => $c->name, 'type' => $c->type,
        'calculation_base' => $c->calculation_base, 'percentage' => $c->percentage,
    ]);
    $wizardExistingComponents = $employee->currentSalary?->components->map(fn ($c) => [
        'component_type' => $c->component_type, 'component_id' => $c->component_id,
    ]) ?? [];
    $wizardPfEsiConfig = $pfEsiConfig ? [
        'pf_wage_ceiling' => (float) $pfEsiConfig->pf_wage_ceiling,
        'esi_wage_ceiling' => (float) $pfEsiConfig->esi_wage_ceiling,
    ] : null;
@endphp
@push('scripts')
<script>
    window.__employeeWizard = {
        activeTab: {{ (int) $activeTab }},
        earningsComponents: @json($wizardEarningsComponents),
        deductionsComponents: @json($wizardDeductionsComponents),
        existingComponents: @json($wizardExistingComponents),
        pfEsiConfig: @json($wizardPfEsiConfig),
        documentTypes: {
            aadhaar: 'Aadhaar', pan: 'PAN', bank_proof: 'Bank Proof', appointment_letter: 'Appointment Letter',
            employment_agreement: 'Employment Agreement', education_certificate: 'Education Certificate',
            experience_certificate: 'Experience Certificate', contractor_id: 'Contractor ID', passport: 'Passport', other: 'Other',
        },
    };
</script>
<script>
    (function () {
        const TOTAL_TABS = 10;
        const categorySelect = document.getElementById('employee_category');
        const navTab6Item = document.getElementById('nav-tab-6-item');
        const sameAsCurrent = document.getElementById('same_as_current_address');
        const permanentFields = document.getElementById('permanent-address-fields');
        const dobInput = document.getElementById('date_of_birth');
        const ageDisplay = document.getElementById('age_display');
        const systemClassificationDisplay = document.getElementById('system_classification_display');
        const displayNameInput = document.getElementById('display_name');
        let displayNameEdited = !!(displayNameInput && displayNameInput.value.trim() !== '');

        function tab6Visible() {
            return categorySelect && categorySelect.value === 'contract_labour';
        }

        // FSD — searchable dropdowns for the master-data pickers (Department,
        // Designation, Employee Type, Shift, Reporting To, Contractor, State,
        // Salary Slab, Bank). Progressively enhances the existing <select>
        // with a type-to-filter text box; the native <select> stays the
        // actual form field (just visually hidden) so submission/validation/
        // old-value repopulation all keep working unchanged.
        function makeSearchable(select) {
            if (!select || select.dataset.searchableInit) return;
            select.dataset.searchableInit = '1';

            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);
            select.classList.add('d-none');

            const input = document.createElement('input');
            input.type = 'text';
            input.className = (select.className || 'form-select').replace('d-none', '').trim();
            input.placeholder = 'Search…';
            input.autocomplete = 'off';
            wrapper.appendChild(input);

            const list = document.createElement('div');
            list.className = 'list-group position-absolute w-100 shadow-sm';
            list.style.cssText = 'z-index:1000;max-height:220px;overflow-y:auto;display:none;';
            wrapper.appendChild(list);

            function optionsData() {
                return Array.from(select.options).filter(o => o.value !== '').map(o => ({ value: o.value, label: o.textContent }));
            }
            function syncInputFromSelect() {
                const opt = select.options[select.selectedIndex];
                input.value = (opt && opt.value !== '') ? opt.textContent.trim() : '';
            }
            function renderList(filter) {
                const q = (filter || '').toLowerCase();
                const matches = optionsData().filter(o => o.label.toLowerCase().includes(q));
                list.innerHTML = matches.length
                    ? matches.map(o => `<button type="button" class="list-group-item list-group-item-action" data-value="${o.value}">${o.label}</button>`).join('')
                    : '<div class="list-group-item text-muted">No matches</div>';
                list.style.display = 'block';
                list.querySelectorAll('[data-value]').forEach(function (btn) {
                    btn.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        select.value = btn.dataset.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        syncInputFromSelect();
                        list.style.display = 'none';
                    });
                });
            }
            input.addEventListener('focus', function () { renderList(''); });
            input.addEventListener('input', function () { renderList(input.value); });
            input.addEventListener('blur', function () {
                setTimeout(function () { list.style.display = 'none'; syncInputFromSelect(); }, 150);
            });
            syncInputFromSelect();
        }

        function refreshTab6Nav() {
            if (!navTab6Item) return;
            navTab6Item.style.display = tab6Visible() ? '' : 'none';
        }

        function refreshSystemClassification() {
            if (!categorySelect || !systemClassificationDisplay) return;
            const labels = { staff: 'Staff', company_labour: 'Company Labour', contract_labour: 'Contract Labour' };
            systemClassificationDisplay.value = labels[categorySelect.value] || '';
        }

        function togglePermanentFields() {
            if (!sameAsCurrent || !permanentFields) return;
            permanentFields.style.display = sameAsCurrent.checked ? 'none' : '';
        }

        function copyCurrentToPermanentAddress() {
            if (!sameAsCurrent || !sameAsCurrent.checked) return;
            const map = {
                address_line1: 'permanent_address_line1', address_line2: 'permanent_address_line2',
                city: 'permanent_city', district: 'permanent_district', state: 'permanent_state', pincode: 'permanent_pincode',
            };
            Object.entries(map).forEach(([src, dest]) => {
                const s = document.getElementById(src);
                const d = document.querySelector(`[name="${dest}"]`);
                if (s && d) d.value = s.value;
            });
        }

        function refreshAge() {
            if (!dobInput || !ageDisplay) return;
            if (!dobInput.value) { ageDisplay.value = ''; return; }
            const dob = new Date(dobInput.value);
            if (isNaN(dob.getTime())) { ageDisplay.value = ''; return; }
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
            ageDisplay.value = age >= 0 ? age : '';
        }

        function refreshDisplayName() {
            if (!displayNameInput || displayNameEdited) return;
            const first = document.getElementById('first_name')?.value || '';
            const middle = document.getElementById('middle_name')?.value || '';
            const last = document.getElementById('last_name')?.value || '';
            displayNameInput.value = [first, middle, last].filter(Boolean).join(' ');
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', function () { refreshTab6Nav(); refreshSystemClassification(); });
            refreshTab6Nav();
            refreshSystemClassification();
        }
        if (sameAsCurrent && permanentFields) {
            sameAsCurrent.addEventListener('change', function () { togglePermanentFields(); copyCurrentToPermanentAddress(); });
            togglePermanentFields();
            ['address_line1', 'address_line2', 'city', 'district', 'state', 'pincode'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.addEventListener('input', copyCurrentToPermanentAddress); el.addEventListener('change', copyCurrentToPermanentAddress); }
            });
        }
        if (dobInput) { dobInput.addEventListener('change', refreshAge); refreshAge(); }
        if (displayNameInput) {
            displayNameInput.addEventListener('input', function () { displayNameEdited = displayNameInput.value.trim() !== ''; });
            ['first_name', 'middle_name', 'last_name'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', refreshDisplayName);
            });
        }

        // Any "Save"-while-staying button (already-complete employee, tabs
        // 1-6) submits next_tab equal to its own pane, set once at load time.
        document.querySelectorAll('.wizard-save-stay').forEach(function (btn) {
            const pane = btn.closest('[data-tab-pane]');
            if (pane) btn.value = pane.dataset.tabPane;
        });

        // ── Tab navigation across all 10 panes ──
        const navButtons = Array.from(document.querySelectorAll('[data-nav-tab]'));
        const panes = Array.from(document.querySelectorAll('[data-tab-pane]'));

        function isTabEnabled(n) {
            if (n === 6) return tab6Visible();
            return n >= 1 && n <= TOTAL_TABS;
        }
        function nextEnabledTab(from, direction) {
            let n = from + direction;
            while (n >= 1 && n <= TOTAL_TABS && !isTabEnabled(n)) n += direction;
            return Math.min(Math.max(n, 1), TOTAL_TABS);
        }
        function showTab(n) {
            panes.forEach(function (p) { p.style.display = (parseInt(p.dataset.tabPane, 10) === n) ? '' : 'none'; });
            navButtons.forEach(function (b) { b.classList.toggle('active', parseInt(b.dataset.navTab, 10) === n); });
        }
        navButtons.forEach(function (b) {
            b.addEventListener('click', function () { showTab(parseInt(b.dataset.navTab, 10)); });
        });
        document.querySelectorAll('.wizard-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const pane = btn.closest('[data-tab-pane]');
                const current = parseInt(pane.dataset.tabPane, 10);
                const requiredFields = pane.querySelectorAll('[required]');
                for (const f of requiredFields) { if (!f.reportValidity()) return; }
                showTab(nextEnabledTab(current, 1));
            });
        });
        document.querySelectorAll('.wizard-prev').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const pane = btn.closest('[data-tab-pane]');
                const current = parseInt(pane.dataset.tabPane, 10);
                showTab(nextEnabledTab(current, -1));
            });
        });

        showTab(isTabEnabled(window.__employeeWizard.activeTab) ? window.__employeeWizard.activeTab : 1);

        document.querySelectorAll('[data-searchable]').forEach(makeSearchable);

        // ── Tab 8: Bank Information — required fields only when Payment Mode = Bank Transfer ──
        const paymentMode = document.getElementById('payment_mode');
        if (paymentMode) {
            function refreshBankRequired() {
                const required = paymentMode.value === 'bank_transfer';
                document.querySelectorAll('.bank-transfer-field').forEach(function (el) {
                    el.toggleAttribute('required', required);
                });
            }
            paymentMode.addEventListener('change', refreshBankRequired);
            refreshBankRequired();
        }

        // Account Number / Confirm Account Number are masked (password-style)
        // by default; the eye-icon toggles plain-text visibility for the user
        // to proofread what they typed.
        document.querySelectorAll('.masked-toggle-btn').forEach(function (btn) {
            const field = btn.closest('.input-group').querySelector('.masked-toggle-field');
            btn.addEventListener('click', function () {
                const showing = field.type === 'text';
                field.type = showing ? 'password' : 'text';
                btn.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        });

        // ── Tab 9: PF/ESI/TDS auto-default from PfEsiConfig ceilings + selected Salary Slab's TDS % ──
        const pfEsiConfig = window.__employeeWizard.pfEsiConfig;
        const salarySlabSelect = document.getElementById('salary_slab_id');
        const basicInput = document.getElementById('salary_basic');
        // FSD Rule 8 — PF/ESI/TDS are owned by Tab 7 (Statutory Information);
        // Tab 9 only *suggests* a value into those same checkboxes (from the
        // wage ceilings / selected Salary Slab) and mirrors their state in a
        // read-only summary — there is no separate, competing set of
        // checkboxes here to fall out of sync with Tab 7.
        const pfCheckbox = document.getElementById('is_pf_applicable');
        const esiCheckbox = document.getElementById('is_esi_applicable');
        const tdsCheckbox = document.getElementById('is_tds_applicable');
        const pfDisplay = document.getElementById('salary_pf_display');
        const esiDisplay = document.getElementById('salary_esi_display');
        const tdsDisplay = document.getElementById('salary_tds_display');
        function refreshStatutoryDisplays() {
            if (pfCheckbox && pfDisplay) pfDisplay.textContent = pfCheckbox.checked ? 'Applicable' : 'Not Applicable';
            if (esiCheckbox && esiDisplay) esiDisplay.textContent = esiCheckbox.checked ? 'Applicable' : 'Not Applicable';
            if (tdsCheckbox && tdsDisplay) tdsDisplay.textContent = tdsCheckbox.checked ? 'Applicable' : 'Not Applicable';
        }
        [pfCheckbox, esiCheckbox, tdsCheckbox].forEach(function (cb) {
            if (cb) cb.addEventListener('change', refreshStatutoryDisplays);
        });

        function baseAllowanceTotal() {
            const ids = ['salary_basic', 'salary_hra', 'salary_da', 'salary_ta', 'salary_medical', 'salary_special'];
            return ids.reduce((sum, id) => sum + (parseFloat(document.getElementById(id)?.value) || 0), 0);
        }
        function earningComponentTotal() {
            return Array.from(document.querySelectorAll('#salaryComponentsBody tr')).reduce(function (sum, tr) {
                return sum + (tr.dataset.kind === 'earning' ? (parseFloat(tr.dataset.amount) || 0) : 0);
            }, 0);
        }
        function grossSalary() {
            return baseAllowanceTotal() + earningComponentTotal();
        }
        const grossDisplay = document.getElementById('salary_gross_display');
        function refreshGrossSalaryDisplay() {
            if (grossDisplay) grossDisplay.value = grossSalary().toFixed(2);
        }
        function refreshStatutoryDefaults() {
            refreshGrossSalaryDisplay();
            if (pfEsiConfig && basicInput && pfCheckbox) {
                const basic = parseFloat(basicInput.value) || 0;
                if (basic > 0) pfCheckbox.checked = basic <= pfEsiConfig.pf_wage_ceiling;
            }
            if (pfEsiConfig && esiCheckbox) {
                const gross = grossSalary();
                if (gross > 0) esiCheckbox.checked = gross <= pfEsiConfig.esi_wage_ceiling;
            }
            if (salarySlabSelect && tdsCheckbox) {
                const opt = salarySlabSelect.options[salarySlabSelect.selectedIndex];
                const tdsPct = opt ? parseFloat(opt.dataset.tdsPercentage) || 0 : 0;
                if (salarySlabSelect.value) tdsCheckbox.checked = tdsPct > 0;
            }
            refreshStatutoryDisplays();
        }
        if (salarySlabSelect) salarySlabSelect.addEventListener('change', refreshStatutoryDefaults);
        ['salary_basic', 'salary_hra', 'salary_da', 'salary_ta', 'salary_medical', 'salary_special'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', refreshStatutoryDefaults);
        });

        // ── Tab 9: Salary Components add-row — ONE combined, searchable
        // "Salary Component" picker (Earnings + Deductions together);
        // Component Type/Calculation Type/Calculation Base/Amount-Percentage/
        // Calculated Amount are all auto-displayed from whichever component
        // was picked, never separately chosen by the user. ──
        const componentsBody = document.getElementById('salaryComponentsBody');
        const addComponentBtn = document.getElementById('addSalaryComponentRow');
        let componentRowIndex = 0;

        function combinedComponentOptions() {
            const earnings = window.__employeeWizard.earningsComponents
                .map(c => `<option value="${c.id}" data-kind="earning" data-type="${c.type}" data-base="${c.calculation_base || ''}" data-rate="${c.percentage}">${c.name}</option>`).join('');
            const deductions = window.__employeeWizard.deductionsComponents
                .map(c => `<option value="${c.id}" data-kind="deduction" data-type="${c.type}" data-base="${c.calculation_base || ''}" data-rate="${c.percentage}">${c.name}</option>`).join('');
            return '<option value="">Select…</option>'
                + (earnings ? `<optgroup label="Earnings">${earnings}</optgroup>` : '')
                + (deductions ? `<optgroup label="Deductions">${deductions}</optgroup>` : '');
        }
        function computeAmount(rate, calcType, calcBase) {
            const ctc = parseFloat(document.getElementById('salary_ctc')?.value) || 0;
            const basic = parseFloat(basicInput?.value) || 0;
            if (calcType === 'percentage') {
                const base = (calcBase || '').toLowerCase().includes('ctc') ? ctc : basic;
                return Math.round(base * rate / 100 * 100) / 100;
            }
            return rate;
        }
        function addComponentRow(preset) {
            const idx = componentRowIndex++;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <select name="components[${idx}][component_id]" class="form-select form-select-sm component-select"></select>
                    <input type="hidden" name="components[${idx}][component_type]" class="component-type-hidden">
                </td>
                <td class="component-type-display text-muted">—</td>
                <td class="component-calc-type text-muted">—</td>
                <td class="component-calc-base text-muted">—</td>
                <td class="component-rate text-muted">—</td>
                <td class="component-calc-amount text-muted">—</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-component-row"><i class="bi bi-x"></i></button></td>
            `;
            componentsBody.appendChild(tr);

            const select = tr.querySelector('.component-select');
            const typeHidden = tr.querySelector('.component-type-hidden');
            const typeDisplay = tr.querySelector('.component-type-display');
            const calcTypeCell = tr.querySelector('.component-calc-type');
            const calcBaseCell = tr.querySelector('.component-calc-base');
            const rateCell = tr.querySelector('.component-rate');
            const calcAmountCell = tr.querySelector('.component-calc-amount');

            select.innerHTML = combinedComponentOptions();

            function refreshComponentPreview() {
                const opt = select.options[select.selectedIndex];
                if (!opt || !opt.value) {
                    typeHidden.value = '';
                    tr.dataset.kind = ''; tr.dataset.amount = '0';
                    typeDisplay.textContent = '—'; calcTypeCell.textContent = '—'; calcBaseCell.textContent = '—'; rateCell.textContent = '—'; calcAmountCell.textContent = '—';
                    refreshGrossSalaryDisplay();
                    return;
                }
                const kind = opt.dataset.kind;
                const calcType = opt.dataset.type;
                const calcBase = opt.dataset.base;
                const rate = parseFloat(opt.dataset.rate) || 0;
                const amount = computeAmount(rate, calcType, calcBase);
                typeHidden.value = kind;
                tr.dataset.kind = kind;
                tr.dataset.amount = amount;
                typeDisplay.textContent = kind ? (kind.charAt(0).toUpperCase() + kind.slice(1)) : '—';
                calcTypeCell.textContent = calcType ? (calcType.charAt(0).toUpperCase() + calcType.slice(1)) : '—';
                calcBaseCell.textContent = calcType === 'percentage' ? (calcBase || '—') : '—';
                rateCell.textContent = calcType === 'percentage' ? (rate + '%') : ('₹' + rate.toFixed(2));
                calcAmountCell.textContent = '₹' + amount.toFixed(2);
                refreshGrossSalaryDisplay();
            }

            select.addEventListener('change', refreshComponentPreview);
            [document.getElementById('salary_ctc'), basicInput].forEach(function (el) {
                if (el) el.addEventListener('input', refreshComponentPreview);
            });
            tr.querySelector('.remove-component-row').addEventListener('click', function () { tr.remove(); refreshGrossSalaryDisplay(); });

            if (preset && preset.component_id) select.value = preset.component_id;
            refreshComponentPreview();
            makeSearchable(select);
        }
        if (addComponentBtn) addComponentBtn.addEventListener('click', function () { addComponentRow(null); });
        (window.__employeeWizard.existingComponents || []).forEach(function (c) { addComponentRow(c); });

        // ── Tab 10: Documents add-row ──
        const documentsBody = document.getElementById('documentsBody');
        const addDocumentBtn = document.getElementById('addDocumentRow');
        let documentRowIndex = 0;

        function addDocumentRow() {
            const idx = documentRowIndex++;
            const typeOptions = Object.entries(window.__employeeWizard.documentTypes)
                .map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><select name="documents[${idx}][document_type]" class="form-select form-select-sm" required>${typeOptions}</select></td>
                <td><input type="text" name="documents[${idx}][document_number]" class="form-control form-control-sm"></td>
                <td><input type="date" name="documents[${idx}][issue_date]" class="form-control form-control-sm"></td>
                <td><input type="date" name="documents[${idx}][expiry_date]" class="form-control form-control-sm"></td>
                <td><input type="file" name="documents[${idx}][file]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required></td>
                <td><input type="text" name="documents[${idx}][remarks]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-document-row"><i class="bi bi-x"></i></button></td>
            `;
            documentsBody.appendChild(tr);
            tr.querySelector('.remove-document-row').addEventListener('click', function () { tr.remove(); });
        }
        if (addDocumentBtn) addDocumentBtn.addEventListener('click', addDocumentRow);
        addDocumentRow();
    })();
</script>
@endpush
