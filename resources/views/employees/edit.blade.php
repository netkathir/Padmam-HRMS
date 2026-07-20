@extends('layouts.app')

@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')
@section('page-subtitle', $employee->full_name)
@section('back-url', route('employees.index'))

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($employee->is_draft)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> This employee is still a draft. Complete Personal, Contact, and Address Information here, then use Employee Slab to add Employment, Bank, and Salary details.</div>
    @endif
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto overflow-y-hidden" id="employeeWizardNav">
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="2" data-step-label="Personal Information">Personal Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="3" data-step-label="Contact Information">Contact Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="4" data-step-label="Address Information">Address Information</button></li>
            </ul>

            <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data" id="employeeWizardForm">
                @csrf @method('PUT')
                @include('employees._tabs_1_7', ['employee' => $employee])
            </form>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('employee-slab.edit', $employee) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-layers"></i> Go to Employee Slab</a>
        <a href="{{ route('employee-document.create', ['employee' => $employee->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-text"></i> Go to Employee Document</a>
    </div>
@endsection

@push('scripts')
<script>
    window.__employeeWizard = {
        activeTab: {{ (int) $activeTab }},
    };
</script>
<script>
    (function () {
        const TOTAL_TABS = 4;
        const sameAsCurrent = document.getElementById('same_as_current_address');
        const permanentFields = document.getElementById('permanent-address-fields');
        const dobInput = document.getElementById('date_of_birth');
        const ageDisplay = document.getElementById('age_display');
        const displayNameInput = document.getElementById('display_name');
        let displayNameEdited = !!(displayNameInput && displayNameInput.value.trim() !== '');

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

        // ── Tab navigation across the 3 panes (Personal/Contact/Address) ──
        const navButtons = Array.from(document.querySelectorAll('[data-nav-tab]'));
        const panes = Array.from(document.querySelectorAll('[data-tab-pane]'));

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

        // Any "Save"-while-staying button submits next_tab equal to its own pane.
        document.querySelectorAll('.wizard-save-stay').forEach(function (btn) {
            const pane = btn.closest('[data-tab-pane]');
            if (pane) btn.value = pane.dataset.tabPane;
        });

        const start = window.__employeeWizard.activeTab;
        showTab(start >= 2 && start <= TOTAL_TABS ? start : 2);
    })();
</script>
@endpush
