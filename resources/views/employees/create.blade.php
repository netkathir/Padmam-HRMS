@extends('layouts.app')

@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('page-subtitle', 'Create a new employee record')

@section('content')
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto overflow-y-hidden" id="employeeWizardNav">
                <li class="nav-item"><button type="button" class="nav-link active" data-nav-tab="2" data-step-label="Personal Information">Personal Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="3" data-step-label="Contact Information">Contact Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="4" data-step-label="Address Information">Address Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="5" data-step-label="Employment Information">Employment Information</button></li>
                <li class="nav-item" id="nav-tab-6-item"><button type="button" class="nav-link" data-nav-tab="6" data-step-label="Contract Labour Information">Contract Labour Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="8" data-step-label="Bank Information">Bank Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="9" data-step-label="Designation &amp; Salary">Designation &amp; Salary</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="10" data-step-label="Employee Documents">Employee Documents</button></li>
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
                            <input type="text" name="bank_details[account_holder_name]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Account Number</label>
                            <div class="input-group">
                                <input type="password" name="bank_details[account_number]" class="form-control masked-toggle-field" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm Account Number</label>
                            <div class="input-group">
                                <input type="password" name="bank_details[account_number_confirmation]" class="form-control masked-toggle-field" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="bank_details[ifsc_code]" class="form-control" style="text-transform:uppercase">
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

                {{-- ── Tab 9: Designation & Salary ─────────────────────────── --}}
                <div class="tab-pane" data-tab-pane="9">
                    <div class="row g-3">
                        <div class="col-12"><h6 class="fw-bold border-bottom pb-2">Designation</h6></div>
                        <div class="col-md-3">
                            <label class="form-label">Employee Category</label>
                            @php $desigCategory = old('salary.designation_employee_category'); @endphp
                            <select name="salary[designation_employee_category]" id="designation_employee_category" class="form-select">
                                <option value="">Select</option>
                                <option value="company" {{ $desigCategory == 'company' ? 'selected' : '' }}>Company</option>
                                <option value="contractor" {{ $desigCategory == 'contractor' ? 'selected' : '' }}>Contractor</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="salary[designation_employee_type]" id="designation_employee_type" class="form-select" data-selected="{{ old('salary.designation_employee_type') }}">
                                <option value="">Select Category first</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="designation-contractor-field" style="display:none;">
                            <label class="form-label">Contractor Name</label>
                            <select name="salary[designation_contractor_id]" id="designation_contractor_id" class="form-select" data-searchable>
                                <option value="">Select</option>
                                @foreach ($contractors as $c)
                                    <option value="{{ $c->id }}" {{ old('salary.designation_contractor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <select name="salary[department_id]" class="form-select" data-searchable>
                                <option value="">Select</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Designation</label>
                            <input type="text" name="salary[designation]" class="form-control" value="{{ old('salary.designation') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Biometric ID <span class="text-danger">*</span></label>
                            <input type="text" name="biometric_id" class="form-control @error('biometric_id') is-invalid @enderror" value="{{ old('biometric_id') }}" required>
                            @error('biometric_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 mt-2"><h6 class="fw-bold border-bottom pb-2">Salary</h6></div>
                        <div class="col-md-6">
                            <label class="form-label">Salary Slab</label>
                            <select name="salary[salary_slab_id]" id="salary_slab_id" class="form-select" data-searchable>
                                <option value="">— None —</option>
                                @foreach($salarySlabs as $slab)
                                <option value="{{ $slab->id }}" {{ old('salary.salary_slab_id') == $slab->id ? 'selected' : '' }}>{{ $slab->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">The employee's entire salary structure is inherited from the selected slab — nothing below is manually entered.</div>
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

                        <div class="col-12 mt-2"><h6 class="fw-bold border-bottom pb-2">Overtime Applicability</h6></div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="hidden" name="is_ot_applicable" value="0">
                                <input type="checkbox" name="is_ot_applicable" id="is_ot_applicable" class="form-check-input" value="1" {{ old('is_ot_applicable', false) ? 'checked' : '' }}>
                                <label class="form-check-label">OT Applicable</label>
                            </div>
                        </div>

                        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">TDS / PF / ESI Percentages <small class="fw-normal text-muted">(read-only — from the selected Salary Slab)</small></h6></div>
                        <div class="col-md-2">
                            <label class="form-label">TDS %</label>
                            <input type="text" id="inherited_tds_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">PF Employee %</label>
                            <input type="text" id="inherited_pf_employee_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">PF Employer %</label>
                            <input type="text" id="inherited_pf_employer_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ESI Employee %</label>
                            <input type="text" id="inherited_esi_employee_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ESI Employer %</label>
                            <input type="text" id="inherited_esi_employer_percentage" class="form-control" value="" disabled>
                        </div>

                        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Salary Structure <small class="fw-normal text-muted">(read-only — from the selected Salary Slab)</small></h6></div>
                        <div class="col-md-3">
                            <label class="form-label">Basic Salary (₹)</label>
                            <input type="text" id="inherited_basic_salary" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gross Salary (₹)</label>
                            <input type="text" id="inherited_gross_salary" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CTC (₹)</label>
                            <input type="text" id="inherited_ctc" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employer Contribution (₹)</label>
                            <input type="text" id="inherited_employer_contributions" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Deductions (₹)</label>
                            <input type="text" id="inherited_total_deductions" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Net Salary (₹)</label>
                            <input type="text" id="inherited_net_salary" class="form-control" value="" disabled>
                        </div>

                        <div class="col-12 mt-2">
                            <h6 class="fw-bold border-bottom pb-2">Salary Components <small class="fw-normal text-muted">(read-only — from the selected Salary Slab)</small></h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="small text-muted">Earnings Components</h6>
                                    <table class="table table-sm">
                                        <thead><tr><th>Component</th><th>Calc. Type</th><th>Calc. Base</th><th>Amount</th></tr></thead>
                                        <tbody id="inherited_earning_components"><tr><td colspan="4" class="text-muted">Select a Salary Slab above</td></tr></tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small text-muted">Deduction Components</h6>
                                    <table class="table table-sm">
                                        <thead><tr><th>Component</th><th>Calc. Type</th><th>Calc. Base</th><th>Amount</th></tr></thead>
                                        <tbody id="inherited_deduction_components"><tr><td colspan="4" class="text-muted">Select a Salary Slab above</td></tr></tbody>
                                    </table>
                                </div>
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

@push('scripts')
<script>
    window.__employeeWizard = {
        // Full base-path-aware URL template (the app may be deployed in a
        // subdirectory, e.g. /Padmam-HRMS/public, so a root-relative
        // '/salary-slab-breakdown/…' would resolve to the wrong host root).
        salarySlabBreakdownUrl: @json(route('employees.salary-slab-breakdown', ['salarySlab' => 'SLAB_ID'])),
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

        // Step numbers are computed, not hardcoded — Contract Labour
        // Information is only ever visible for the Contract Labour category,
        // so a static "6. Bank Information" would show a gap (5 skipped)
        // whenever it's hidden. Recomputed every time visibility changes.
        function renumberNavTabs() {
            Array.from(document.querySelectorAll('#employeeWizardNav .nav-item'))
                .filter(function (li) { return li.style.display !== 'none'; })
                .forEach(function (li, index) {
                    var btn = li.querySelector('button[data-step-label]');
                    if (btn) btn.textContent = (index + 1) + '. ' + btn.dataset.stepLabel;
                });
        }

        function refreshTab6Nav() {
            if (!navTab6Item) return;
            navTab6Item.style.display = tab6Visible() ? '' : 'none';
            renumberNavTabs();
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
                refreshEmployeeCodePreview();
            });
            refreshTab6Nav();
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
            if (n === 1 || n === 7) return false; // removed: Employee Classification / Statutory Information
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

        showTab(2);

        document.querySelectorAll('[data-searchable]').forEach(makeSearchable);

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

        // ── Tab 9: Designation & Salary — Employee Category drives Type's
        // options and whether Contractor Name is shown/required. ──
        const desigCategorySelect = document.getElementById('designation_employee_category');
        const desigTypeSelect = document.getElementById('designation_employee_type');
        const desigContractorField = document.getElementById('designation-contractor-field');
        const desigContractorSelect = document.getElementById('designation_contractor_id');
        const DESIGNATION_TYPE_OPTIONS = {
            company: [['staff', 'Staff'], ['labor', 'Labor']],
            contractor: [['contractor_staff', 'Contractor Staff'], ['contractor_labor', 'Contractor Labor']],
        };
        function refreshDesignationType(restorePrevious) {
            if (!desigCategorySelect || !desigTypeSelect) return;
            const category = desigCategorySelect.value;
            const previous = restorePrevious ? desigTypeSelect.dataset.selected : desigTypeSelect.value;
            const options = DESIGNATION_TYPE_OPTIONS[category] || [];
            desigTypeSelect.innerHTML = options.length
                ? options.map(([v, label]) => `<option value="${v}">${label}</option>`).join('')
                : '<option value="">Select Category first</option>';
            if (previous && options.some(([v]) => v === previous)) {
                desigTypeSelect.value = previous;
            }
        }
        function refreshDesignationContractor() {
            if (!desigCategorySelect || !desigContractorField) return;
            const isContractor = desigCategorySelect.value === 'contractor';
            desigContractorField.style.display = isContractor ? '' : 'none';
            if (desigContractorSelect) desigContractorSelect.toggleAttribute('required', isContractor);
        }
        if (desigCategorySelect) {
            refreshDesignationType(true);
            refreshDesignationContractor();
            desigCategorySelect.addEventListener('change', function () {
                refreshDesignationType(false);
                refreshDesignationContractor();
            });
        }

        // ── Tab 9: Designation & Salary — the selected Salary Slab is the
        // single source of truth; its whole breakdown is fetched read-only
        // (never computed/entered here). ──
        const salarySlabSelect = document.getElementById('salary_slab_id');

        // Same field-based layout as the Salary Slab Master's own
        // create/edit form (label + boxed input per value) — these stay
        // `disabled` so they're never submitted and never editable here;
        // the Salary Slab itself remains the only place these are entered.
        const inheritedFields = {
            tds_percentage: document.getElementById('inherited_tds_percentage'),
            pf_employee_percentage: document.getElementById('inherited_pf_employee_percentage'),
            pf_employer_percentage: document.getElementById('inherited_pf_employer_percentage'),
            esi_employee_percentage: document.getElementById('inherited_esi_employee_percentage'),
            esi_employer_percentage: document.getElementById('inherited_esi_employer_percentage'),
            basic_salary: document.getElementById('inherited_basic_salary'),
            gross_salary: document.getElementById('inherited_gross_salary'),
            ctc: document.getElementById('inherited_ctc'),
            employer_contributions: document.getElementById('inherited_employer_contributions'),
            total_deductions: document.getElementById('inherited_total_deductions'),
            net_salary: document.getElementById('inherited_net_salary'),
        };
        const earningComponentsBody = document.getElementById('inherited_earning_components');
        const deductionComponentsBody = document.getElementById('inherited_deduction_components');

        function formatNumber(value) {
            return (typeof value === 'number' ? value : parseFloat(value || 0)).toFixed(2);
        }

        function componentRows(components) {
            if (!components || !components.length) return '<tr><td colspan="4" class="text-muted">None</td></tr>';
            return components.map(function (c) {
                return '<tr><td>' + c.name + '</td><td>' + (c.calculation_type ? c.calculation_type.charAt(0).toUpperCase() + c.calculation_type.slice(1) : '—')
                    + '</td><td>' + (c.calculation_base || '—') + '</td><td>₹' + formatNumber(c.amount) + '</td></tr>';
            }).join('');
        }

        function renderSalarySlabBreakdown(data) {
            Object.keys(inheritedFields).forEach(function (key) {
                var field = inheritedFields[key];
                if (field) field.value = data ? formatNumber(data[key]) : '';
            });
            var placeholder = '<tr><td colspan="4" class="text-muted">' + (data ? 'None' : 'Select a Salary Slab above') + '</td></tr>';
            if (earningComponentsBody) earningComponentsBody.innerHTML = data ? componentRows(data.earning_components) : placeholder;
            if (deductionComponentsBody) deductionComponentsBody.innerHTML = data ? componentRows(data.deduction_components) : placeholder;
        }

        function fetchAndRenderSlabBreakdown(slabId) {
            if (!slabId) { renderSalarySlabBreakdown(null); return; }
            var url = window.__employeeWizard.salarySlabBreakdownUrl.replace('SLAB_ID', encodeURIComponent(slabId));
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                    if (!r.ok) { console.error('Salary Slab breakdown request failed with status ' + r.status + ' for slab ' + slabId); return null; }
                    return r.json();
                })
                .then(function (data) {
                    renderSalarySlabBreakdown(data);
                })
                .catch(function (err) {
                    console.error('Salary Slab breakdown request errored for slab ' + slabId + ':', err);
                    renderSalarySlabBreakdown(null);
                });
        }

        if (salarySlabSelect) {
            salarySlabSelect.addEventListener('change', function () { fetchAndRenderSlabBreakdown(salarySlabSelect.value); });
        }
        // The select may already have a value on load if old() repopulated
        // it after a validation failure elsewhere in the form — fetch live
        // so the fields aren't left stuck blank.
        if (salarySlabSelect && salarySlabSelect.value) {
            fetchAndRenderSlabBreakdown(salarySlabSelect.value);
        }

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
            });
        }
    })();
</script>
@endpush
