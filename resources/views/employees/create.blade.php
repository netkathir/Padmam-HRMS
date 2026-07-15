@extends('layouts.app')

@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('page-subtitle', 'Create a new employee record')

@section('content')
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto overflow-y-hidden" id="employeeWizardNav">
                <li class="nav-item"><button type="button" class="nav-link active" data-nav-tab="1">1. Employee Classification</button></li>
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

            <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="employeeWizardForm">
                @csrf
                @include('employees._tabs_1_7', ['employee' => null])

                {{--
                    Tabs 8-10 (Bank/Salary/Documents) are part of THIS SAME
                    form/submission — the employee doesn't exist yet, so
                    there's no id to post these to separately. Each section
                    is entirely optional: leave it blank and add it later
                    from Edit, or fill it in now and it's saved together with
                    the employee in one request. Field names are namespaced
                    (bank_details[...], salary[...]) so they don't collide
                    with Tabs 1-7's fields, and are only validated/persisted
                    server-side if the user actually filled them in.
                --}}

                {{-- ── Tab 8: Bank Information ─────────────────────────── --}}
                <div class="tab-pane" data-tab-pane="8">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Payment Mode</label>
                            <select name="bank_details[payment_mode]" id="payment_mode" class="form-select">
                                <option value="">— Not Set —</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bank Name</label>
                            <select name="bank_details[bank_id]" class="form-select" data-searchable>
                                <option value="">Select</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Not listed? Type it in "Other Bank Name" instead.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Other Bank Name (if not listed above)</label>
                            <input type="text" name="bank_details[bank_name]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Account Holder Name</label>
                            <input type="text" name="bank_details[account_holder_name]" class="form-control bank-transfer-field">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Account Number</label>
                            <div class="input-group">
                                <input type="password" name="bank_details[account_number]" class="form-control bank-transfer-field masked-toggle-field" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm Account Number</label>
                            <div class="input-group">
                                <input type="password" name="bank_details[account_number_confirmation]" class="form-control bank-transfer-field masked-toggle-field" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="bank_details[ifsc_code]" class="form-control bank-transfer-field" style="text-transform:uppercase">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank Branch</label>
                            <input type="text" name="bank_details[branch_name]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Account Type</label>
                            <select name="bank_details[account_type]" class="form-select">
                                <option value="">Select</option>
                                <option value="savings">Savings</option>
                                <option value="current">Current</option>
                                <option value="salary">Salary</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                        <button type="submit" name="next_tab" value="9" class="btn btn-primary">Save &amp; Next</button>
                        <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>

                {{-- ── Tab 9: Salary Structure ─────────────────────────── --}}
                <div class="tab-pane" data-tab-pane="9">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Salary Slab</label>
                            <select name="salary[salary_slab_id]" id="salary_slab_id" class="form-select" data-searchable>
                                <option value="">— None —</option>
                                @foreach($salarySlabs as $slab)
                                <option value="{{ $slab->id }}" data-tds-percentage="{{ $slab->tds_percentage }}">
                                    {{ $slab->name }} (₹{{ number_format($slab->min_ctc) }} – ₹{{ number_format($slab->max_ctc) }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CTC (₹)</label>
                            <input type="number" name="salary[ctc]" id="salary_ctc" step="0.01" min="0" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Basic Salary (₹)</label>
                            <input type="number" name="salary[basic_salary]" id="salary_basic" step="0.01" min="0" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">HRA (₹)</label>
                            <input type="number" name="salary[hra]" id="salary_hra" step="0.01" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">DA (₹)</label>
                            <input type="number" name="salary[da]" id="salary_da" step="0.01" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TA (₹)</label>
                            <input type="number" name="salary[ta]" id="salary_ta" step="0.01" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Medical Allowance (₹)</label>
                            <input type="number" name="salary[medical_allowance]" id="salary_medical" step="0.01" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Special Allowance (₹)</label>
                            <input type="number" name="salary[special_allowance]" id="salary_special" step="0.01" min="0" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" name="salary[effective_from]" class="form-control" value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" name="salary[effective_to]" class="form-control">
                            <div class="form-text">Leave blank for an open-ended (current) structure.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gross Salary (₹)</label>
                            <input type="text" id="salary_gross_display" class="form-control" value="" disabled>
                            <div class="form-text">Auto-calculated from Basic + Allowances + Earning Components.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="Will become Current on Save" disabled>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold border-bottom pb-2 mt-2">Statutory Applicability</h6>
                            <p class="text-muted small mb-0">
                                PF: <strong id="salary_pf_display">—</strong> ·
                                ESI: <strong id="salary_esi_display">—</strong> ·
                                TDS: <strong id="salary_tds_display">—</strong>
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
                        <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>

                {{-- ── Tab 10: Employee Documents ──────────────────────── --}}
                <div class="tab-pane" data-tab-pane="10">
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

                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                        <button type="submit" name="finish" value="1" class="btn btn-success">Save Employee</button>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
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
    $wizardPfEsiConfig = $pfEsiConfig ? [
        'pf_wage_ceiling' => (float) $pfEsiConfig->pf_wage_ceiling,
        'esi_wage_ceiling' => (float) $pfEsiConfig->esi_wage_ceiling,
    ] : null;
@endphp
@push('scripts')
<script>
    window.__employeeWizard = {
        earningsComponents: @json($wizardEarningsComponents),
        deductionsComponents: @json($wizardDeductionsComponents),
        existingComponents: [],
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
        let displayNameEdited = displayNameInput && displayNameInput.value.trim() !== '';
        const employeeTypeSelect = document.getElementById('employee_type_id');
        const contractorSelect = document.getElementById('contractor_id');
        const employeeCodeDisplay = document.getElementById('employee_code_display');

        function isContractLabour() {
            return categorySelect && categorySelect.value === 'contract_labour';
        }

        function tab6Visible() {
            return isContractLabour();
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
                address_line1: 'permanent_address_line1',
                address_line2: 'permanent_address_line2',
                city: 'permanent_city',
                district: 'permanent_district',
                state: 'permanent_state',
                pincode: 'permanent_pincode',
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

        // FSD — Employee Number is auto-generated, prefixed from the first
        // two letters of the selected Employee Type. This preview is
        // read-only feedback only (fetched from a non-consuming endpoint);
        // the real code is always (re-)resolved server-side at submit time.
        let codePreviewToken = 0;
        function refreshEmployeeCodePreview() {
            if (!employeeCodeDisplay || !categorySelect || !employeeTypeSelect) return;
            const category = categorySelect.value;
            const employeeTypeId = employeeTypeSelect.value;
            if (!category || !employeeTypeId) {
                employeeCodeDisplay.value = '';
                return;
            }
            const token = ++codePreviewToken;
            employeeCodeDisplay.value = 'Generating…';
            const params = new URLSearchParams({ employee_category: category, employee_type_id: employeeTypeId });
            if (contractorSelect && contractorSelect.value) params.set('contractor_id', contractorSelect.value);
            fetch('{{ route('employees.preview-code') }}?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (token !== codePreviewToken) return; // a newer request superseded this one
                    employeeCodeDisplay.value = (data && data.code) ? data.code : '';
                })
                .catch(function () {
                    if (token !== codePreviewToken) return;
                    employeeCodeDisplay.value = '';
                });
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                refreshTab6Nav();
                refreshSystemClassification();
                refreshEmployeeCodePreview();
            });
            refreshTab6Nav();
            refreshSystemClassification();
        }
        if (employeeTypeSelect) employeeTypeSelect.addEventListener('change', refreshEmployeeCodePreview);
        if (contractorSelect) contractorSelect.addEventListener('change', refreshEmployeeCodePreview);
        refreshEmployeeCodePreview();
        if (sameAsCurrent && permanentFields) {
            sameAsCurrent.addEventListener('change', function () {
                togglePermanentFields();
                copyCurrentToPermanentAddress();
            });
            togglePermanentFields();
            ['address_line1', 'address_line2', 'city', 'district', 'state', 'pincode'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', copyCurrentToPermanentAddress);
                if (el) el.addEventListener('change', copyCurrentToPermanentAddress);
            });
        }
        if (dobInput) {
            dobInput.addEventListener('change', refreshAge);
            refreshAge();
        }
        if (displayNameInput) {
            displayNameInput.addEventListener('input', function () { displayNameEdited = displayNameInput.value.trim() !== ''; });
            ['first_name', 'middle_name', 'last_name'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', refreshDisplayName);
            });
        }

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
            panes.forEach(function (p) {
                p.style.display = (parseInt(p.dataset.tabPane, 10) === n) ? '' : 'none';
            });
            navButtons.forEach(function (b) {
                b.classList.toggle('active', parseInt(b.dataset.navTab, 10) === n);
            });
        }

        navButtons.forEach(function (b) {
            b.addEventListener('click', function () { showTab(parseInt(b.dataset.navTab, 10)); });
        });

        document.querySelectorAll('.wizard-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const pane = btn.closest('[data-tab-pane]');
                const current = parseInt(pane.dataset.tabPane, 10);
                const requiredFields = pane.querySelectorAll('[required]');
                for (const f of requiredFields) {
                    if (!f.reportValidity()) return;
                }
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

        showTab(1);

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
        refreshStatutoryDisplays();

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
                    <select name="salary[components][${idx}][component_id]" class="form-select form-select-sm component-select"></select>
                    <input type="hidden" name="salary[components][${idx}][component_type]" class="component-type-hidden">
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
                <td><select name="documents[${idx}][document_type]" class="form-select form-select-sm">${typeOptions}</select></td>
                <td><input type="text" name="documents[${idx}][document_number]" class="form-control form-control-sm"></td>
                <td><input type="date" name="documents[${idx}][issue_date]" class="form-control form-control-sm"></td>
                <td><input type="date" name="documents[${idx}][expiry_date]" class="form-control form-control-sm"></td>
                <td><input type="file" name="documents[${idx}][file]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png"></td>
                <td><input type="text" name="documents[${idx}][remarks]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-document-row"><i class="bi bi-x"></i></button></td>
            `;
            documentsBody.appendChild(tr);
            tr.querySelector('.remove-document-row').addEventListener('click', function () { tr.remove(); });
        }
        if (addDocumentBtn) addDocumentBtn.addEventListener('click', addDocumentRow);

        // Documents are entirely optional here (unlike the standalone Tab 10
        // route on Edit, these file inputs aren't marked `required` — the
        // whole section may legitimately be skipped). A row the user added
        // but never actually attached a file to would otherwise fail
        // server-side "file required" validation and block the entire
        // Tabs 1-7 submission just because of one abandoned row — so any
        // such incomplete row is dropped right before the form submits.
        const wizardForm = document.getElementById('employeeWizardForm');
        if (wizardForm) {
            wizardForm.addEventListener('submit', function () {
                document.querySelectorAll('#documentsBody tr').forEach(function (tr) {
                    const fileInput = tr.querySelector('input[type="file"]');
                    if (!fileInput || fileInput.files.length === 0) tr.remove();
                });
                // Same reasoning for Salary Component rows added but left
                // with no component actually selected.
                document.querySelectorAll('#salaryComponentsBody tr').forEach(function (tr) {
                    const select = tr.querySelector('.component-select');
                    if (!select || !select.value) tr.remove();
                });
            });
        }
    })();
</script>
@endpush
