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
        const TOTAL_TABS = 4;
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

        showTab(2);
    })();
</script>
@endpush
