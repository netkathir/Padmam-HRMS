@extends('layouts.app')

@section('title', 'Create Employee')
@section('page-title', 'Create Employee')
@section('page-subtitle', 'Add Personal, Contact, and Address information')
@section('back-url', route('employees.index'))

@section('content')
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto overflow-y-hidden" id="employeeWizardNav">
                <li class="nav-item"><button type="button" class="nav-link active" data-nav-tab="2" data-step-label="Personal Information">Personal Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="3" data-step-label="Contact Information">Contact Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="4" data-step-label="Address Information">Address Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="5" data-step-label="Employee Information">Employee Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="6" data-step-label="Statutory Details">Statutory Details</button></li>
            </ul>

            <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="employeeWizardForm">
                @csrf
                @include('employees._tabs_1_7', ['employee' => null])
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const TOTAL_TABS = 6;
        const sameAsCurrent = document.getElementById('same_as_current_address');
        const permanentFields = document.getElementById('permanent-address-fields');
        const dobInput = document.getElementById('date_of_birth');
        const ageDisplay = document.getElementById('age_display');
        const displayNameInput = document.getElementById('display_name');
        let displayNameEdited = displayNameInput && displayNameInput.value.trim() !== '';

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

        // ── Tab navigation across the 3 panes (Personal/Contact/Address) ──
        const navButtons = Array.from(document.querySelectorAll('[data-nav-tab]'));
        const panes = Array.from(document.querySelectorAll('[data-tab-pane]'));

        /**
         * Validates every [required] field within a wizard pane and returns
         * true only if all pass.
         *
         * Two kinds of "hidden" fields need different handling:
         *  - Toggled off entirely (display:none on an ancestor — e.g. a
         *    PF/ESI/TDS/OT number field whose Yes/No is currently "No", or a
         *    Contract-Labour-only field when Contract Labour isn't selected):
         *    not currently applicable, so it's skipped outright.
         *  - Wrapped by the searchable-dropdown helper (the real <select>
         *    gets .d-none; a visible text-input proxy stands in for it): the
         *    select itself is still the field whose value must be checked,
         *    but reportValidity()/focus() must target the visible proxy
         *    input instead, since you can't focus/report on a hidden field.
         * Calling reportValidity() on a hidden field previously returned
         * false with no visible tooltip at all — the wizard just silently
         * refused to advance. Now the first real failure gets focused and a
         * top-right toast names which field needs attention.
         */
        function validatePaneRequiredFields(pane) {
            const requiredFields = pane.querySelectorAll('[required]');
            for (const f of requiredFields) {
                if (f.disabled) continue;

                const wrapper = f.classList.contains('d-none') ? f.closest('.position-relative') : null;
                const proxyInput = wrapper ? wrapper.querySelector('input[type="text"]') : null;
                const visibleTarget = proxyInput || f;

                if (!proxyInput && f.offsetParent === null) continue; // toggled off — not currently applicable

                if (!f.checkValidity()) {
                    const label = pane.querySelector(`label[for="${f.id}"]`)
                        || (f.closest('.col-12, .col-md, [class*="col-"]')?.querySelector('label'));
                    const fieldName = (label?.textContent || f.name || 'This field').replace('*', '').trim();
                    window.showToast(`"${fieldName}" is required before you can continue.`, 'warning');
                    if (visibleTarget === f) {
                        f.reportValidity();
                    } else {
                        proxyInput.classList.add('is-invalid');
                        proxyInput.focus();
                    }
                    return false;
                }
            }
            return true;
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
                if (!validatePaneRequiredFields(pane)) return;
                showTab(Math.min(current + 1, TOTAL_TABS));
            });
        });
        document.querySelectorAll('.wizard-prev').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const pane = btn.closest('[data-tab-pane]');
                const current = parseInt(pane.dataset.tabPane, 10);
                showTab(Math.max(current - 1, 2));
            });
        });

        // ── Tab 5: Employee Category drives Contract Labour fields / Bank Details visibility ──
        const categorySelect = document.getElementById('employee_category');
        const contractFields = document.getElementById('contract-labour-fields');
        const bankFields = document.getElementById('bank-details-fields');
        const contractorRequiredField = document.getElementById('contractor_id');
        function refreshEmployeeCategoryFields() {
            if (!categorySelect) return;
            const isContractLabour = categorySelect.value === 'contract_labour';
            if (contractFields) contractFields.style.display = isContractLabour ? '' : 'none';
            if (bankFields) bankFields.style.display = isContractLabour ? 'none' : '';
            if (contractorRequiredField) contractorRequiredField.toggleAttribute('required', isContractLabour);
        }
        if (categorySelect) {
            categorySelect.addEventListener('change', refreshEmployeeCategoryFields);
            refreshEmployeeCategoryFields();
        }

        // ── Tab 6: Statutory Details — PF/ESI/TDS/OT Yes/No toggles their number/rate field ──
        function wireYesNoToggle(selectId, fieldId) {
            const select = document.getElementById(selectId);
            const field = document.getElementById(fieldId);
            if (!select || !field) return;
            function refresh() { field.style.display = select.value === 'yes' ? '' : 'none'; }
            select.addEventListener('change', refresh);
            refresh();
        }
        wireYesNoToggle('is_pf_applicable', 'pf-number-field');
        wireYesNoToggle('is_esi_applicable', 'esi-number-field');
        wireYesNoToggle('is_tds_applicable', 'tds-number-field');
        wireYesNoToggle('is_ot_applicable', 'ot-rate-field');

        // ── Tab 6: Gross Salary is always collected. It auto-matches a Salary
        // Slab, but the slab's earnings are only offered back (as a dropdown
        // checklist) when Earnings = Yes; when Earnings = No, only Gross
        // Salary itself is collected. ──
        (function () {
            const earningsToggle = document.getElementById('is_earnings_applicable');
            const basicSalaryInput = document.getElementById('basic_salary');
            const matchedSlabField = document.getElementById('matched-slab-field');
            const slabDisplay = document.getElementById('matched_slab_display');
            const earningsFields = document.getElementById('salary-earnings-fields');
            const earningsRows = document.getElementById('salary-earnings-rows');
            const earningsDropdownToggle = document.getElementById('salary-earnings-toggle');
            if (!basicSalaryInput || !slabDisplay || !earningsRows) return;

            const selectedEarnings = new Set((JSON.parse(earningsRows.dataset.selectedEarnings || '[]')).map(String));
            let debounceTimer = null;

            function earningsApplicable() {
                return !earningsToggle || earningsToggle.value === 'yes';
            }

            function updateDropdownToggleLabel() {
                const count = earningsRows.querySelectorAll('input[type=checkbox]:checked').length;
                earningsDropdownToggle.textContent = count ? (count + ' earning' + (count > 1 ? 's' : '') + ' selected') : 'Select earnings';
            }

            function renderEarnings(earnings) {
                if (!earningsApplicable() || !earnings.length) {
                    earningsFields.style.display = 'none';
                    earningsRows.innerHTML = '';
                    return;
                }
                earningsFields.style.display = '';
                earningsRows.innerHTML = earnings.map(function (e) {
                    const checked = selectedEarnings.has(String(e.component_id)) ? 'checked' : '';
                    return '<div class="form-check">'
                        + '<input type="checkbox" class="form-check-input" name="salary_earnings[]" value="' + e.component_id + '" id="earning_' + e.component_id + '" ' + checked + '>'
                        + '<label class="form-check-label" for="earning_' + e.component_id + '">' + e.name + ' (' + e.rate + '%  ≈ ₹' + e.amount + ')</label>'
                        + '</div>';
                }).join('');
                updateDropdownToggleLabel();
                earningsRows.addEventListener('change', updateDropdownToggleLabel);
            }

            function lookupSlab() {
                const basicSalary = parseFloat(basicSalaryInput.value);
                if (!basicSalary || basicSalary <= 0) {
                    slabDisplay.value = '';
                    matchedSlabField.style.display = 'none';
                    earningsFields.style.display = 'none';
                    earningsRows.innerHTML = '';
                    return;
                }
                matchedSlabField.style.display = '';
                fetch('{{ route('employees.salary-slab-breakdown') }}?basic_salary=' + encodeURIComponent(basicSalary))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.matched) {
                            slabDisplay.value = 'No matching slab for this amount';
                            earningsFields.style.display = 'none';
                            earningsRows.innerHTML = '';
                            return;
                        }
                        slabDisplay.value = data.slab.name;
                        renderEarnings(data.earnings || []);
                    })
                    .catch(function () { slabDisplay.value = ''; });
            }

            basicSalaryInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(lookupSlab, 400);
            });
            if (earningsToggle) {
                earningsToggle.addEventListener('change', function () {
                    if (earningsApplicable()) {
                        if (basicSalaryInput.value) lookupSlab();
                    } else {
                        earningsFields.style.display = 'none';
                        earningsRows.innerHTML = '';
                    }
                });
            }
            if (basicSalaryInput.value) lookupSlab();
        })();

        // ── Tab 5: selecting a Contractor auto-fills Contract Start/End Date ──
        const contractorSelect = document.getElementById('contractor_id');
        const contractStartInput = document.getElementById('contract_start_date');
        const contractEndInput = document.getElementById('contract_end_date');
        function setDateValue(input, value) {
            if (!input) return;
            if (input._flatpickr) {
                input._flatpickr.setDate(value || null, true);
            } else {
                input.value = value || '';
            }
        }
        function refreshContractDatesFromContractor() {
            if (!contractorSelect) return;
            const opt = contractorSelect.options[contractorSelect.selectedIndex];
            const hasSelection = opt && opt.value;
            setDateValue(contractStartInput, hasSelection ? (opt.dataset.agreementStart || '') : '');
            setDateValue(contractEndInput, hasSelection ? (opt.dataset.agreementEnd || '') : '');
        }
        if (contractorSelect) {
            contractorSelect.addEventListener('change', refreshContractDatesFromContractor);
        }

        // ── Account Number / Confirm Account Number show/hide toggle ──
        document.querySelectorAll('.masked-toggle-btn').forEach(function (btn) {
            const field = btn.closest('.input-group').querySelector('.masked-toggle-field');
            btn.addEventListener('click', function () {
                const showing = field.type === 'text';
                field.type = showing ? 'password' : 'text';
                btn.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        });

        // ── Searchable dropdowns (Department/Shift/Contractor/Bank) ──
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
            input.addEventListener('focus', function () { renderList(input.value); });
            input.addEventListener('click', function () { renderList(input.value); });
            input.addEventListener('input', function () { renderList(input.value); });
            input.addEventListener('blur', function () { setTimeout(function () { list.style.display = 'none'; syncInputFromSelect(); }, 150); });
            syncInputFromSelect();
        }
        document.querySelectorAll('[data-searchable]').forEach(makeSearchable);

        showTab(2);
    })();
</script>
@endpush
