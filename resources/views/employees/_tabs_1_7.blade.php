{{--
    Shared Tabs 1-7 field set for the Employee Registration wizard
    (Employee Classification, Personal Information, Contact Information,
    Address Information, Employment Information, Contract Labour
    Information, Statutory Information). Included by both create.blade.php
    and edit.blade.php inside their single Tabs-1-7 form.

    Expects: $employee (Employee|null — null on Create), $currentBranch,
    $departments, $employeeTypes, $contractors, $shifts,
    $managers, $roles, $states, $canOverrideRules, $canSetContractorRate,
    $ruleOptions.
--}}
@php
    $currentCategory = old('employee_category', $employee ? ($employee->primary_employee_type === 'staff' ? 'staff' : $employee->labour_type) : null);
    // Every one of Tabs 1-9 shows a "Save as Draft" button per the FSD,
    // labelled identically either way. For a draft (or brand-new) employee it
    // actually flags the row is_draft=1. Once a record is already complete
    // (is_draft=false), the SAME label instead just persists this tab's data
    // and stays put — reusing it to flip a complete record back into draft
    // status would be a silent, surprising data regression, and there's no
    // partial state left to "save as draft" once registration is finished.
    // (next_tab is filled in client-side by wizard.js, matching the button's
    // own tab-pane).
    $showSaveAsDraft = ! $employee || $employee->is_draft;
@endphp

{{-- FSD Rule 1 — Branch is never shown; it always follows the currently active branch. --}}
<input type="hidden" name="branch_id" value="{{ $currentBranch->id ?? '' }}">

@if ($employee)
    {{-- Biometric ID is manually entered on the Designation & Salary tab
         (its own standalone form on Edit) — carried forward unchanged here
         so this form's own `required` validation still passes when it's
         submitted without visiting that tab. --}}
    <input type="hidden" name="biometric_id" value="{{ $employee->biometric_id }}">
@endif

{{-- ── Tab 2: Personal Information ─────────────────────────────────── --}}
<div class="tab-pane" data-tab-pane="2">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Photo</label>
            @if (! empty($employee?->profile_photo))
                <div class="mb-1"><img src="{{ $employee->profile_photo_url }}" alt="" style="height:48px;width:48px;object-fit:cover;border-radius:50%"></div>
            @endif
            <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png" class="form-control @error('profile_photo') is-invalid @enderror">
            @error('profile_photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">First Name <span class="text-danger">*</span></label>
            <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $employee->first_name ?? '') }}" required>
            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $employee->middle_name ?? '') }}">
            @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $employee->last_name ?? '') }}">
            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Display Name</label>
            <input type="text" name="display_name" id="display_name" class="form-control" value="{{ old('display_name', $employee->display_name ?? '') }}" placeholder="Defaults to full name">
        </div>
        <div class="col-md-3">
            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $employee?->date_of_birth?->format('Y-m-d') ?? '') }}" required>
            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Age</label>
            <input type="text" id="age_display" class="form-control" value="{{ $employee->age ?? '' }}" disabled>
            <div class="form-text">Calculated from Date of Birth.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Gender <span class="text-danger">*</span></label>
            @php $currentGender = old('gender', $employee->gender ?? ''); @endphp
            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                <option value="">Select</option>
                <option value="male" {{ $currentGender == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ $currentGender == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ $currentGender == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Marital Status</label>
            @php $currentMarital = old('marital_status', $employee->marital_status ?? ''); @endphp
            <select name="marital_status" class="form-select">
                <option value="">Select</option>
                <option value="single" {{ $currentMarital == 'single' ? 'selected' : '' }}>Single</option>
                <option value="married" {{ $currentMarital == 'married' ? 'selected' : '' }}>Married</option>
                <option value="divorced" {{ $currentMarital == 'divorced' ? 'selected' : '' }}>Divorced</option>
                <option value="widowed" {{ $currentMarital == 'widowed' ? 'selected' : '' }}>Widowed</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Blood Group</label>
            <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group', $employee->blood_group ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Father / Spouse Name</label>
            <input type="text" name="father_spouse_name" class="form-control @error('father_spouse_name') is-invalid @enderror" value="{{ old('father_spouse_name', $employee->father_spouse_name ?? '') }}">
            @error('father_spouse_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Nationality</label>
            <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $employee->nationality ?? 'Indian') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Religion</label>
            <input type="text" name="religion" class="form-control" value="{{ old('religion', $employee->religion ?? '') }}">
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" disabled>Previous</button>
        <button type="button" class="btn btn-primary wizard-next">Save &amp; Next</button>
        @if($showSaveAsDraft)
            <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
        @else
            <button type="submit" name="next_tab" value="" class="btn btn-outline-primary wizard-save-stay">Save as Draft</button>
        @endif
        <a href="{{ $employee ? route('employees.show', $employee) : route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

{{-- ── Tab 3: Contact Information ──────────────────────────────────── --}}
<div class="tab-pane" data-tab-pane="3">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Official Email <span class="text-danger">*</span></label>
            <input type="email" name="official_email" class="form-control @error('official_email') is-invalid @enderror" value="{{ old('official_email', $employee->official_email ?? '') }}" required>
            @error('official_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Personal Email</label>
            <input type="email" name="personal_email" class="form-control" value="{{ old('personal_email', $employee->personal_email ?? '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Phone <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone ?? '') }}" required>
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Alternate Phone</label>
            <input type="text" name="alternate_phone" class="form-control @error('alternate_phone') is-invalid @enderror" value="{{ old('alternate_phone', $employee->alternate_phone ?? '') }}">
            @error('alternate_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Emergency Contact Name</label>
            <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $employee->emergency_contact_name ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Emergency Contact Phone</label>
            <input type="text" name="emergency_contact_phone" class="form-control @error('emergency_contact_phone') is-invalid @enderror" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone ?? '') }}">
            @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Emergency Contact Relationship</label>
            <input type="text" name="emergency_contact_relationship" class="form-control" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship ?? '') }}">
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
        <button type="button" class="btn btn-primary wizard-next">Save &amp; Next</button>
        @if($showSaveAsDraft)
            <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
        @else
            <button type="submit" name="next_tab" value="" class="btn btn-outline-primary wizard-save-stay">Save as Draft</button>
        @endif
        <a href="{{ $employee ? route('employees.show', $employee) : route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

{{-- ── Tab 4: Address Information ──────────────────────────────────── --}}
<div class="tab-pane" data-tab-pane="4">
    <div class="row g-3">
        <div class="col-12"><h6 class="fw-bold border-bottom pb-2">Current Address</h6></div>
        <div class="col-12">
            <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
            <textarea name="address_line1" id="address_line1" class="form-control @error('address_line1') is-invalid @enderror" rows="2" required>{{ old('address_line1', $employee->address_line1 ?? '') }}</textarea>
            @error('address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Address Line 2</label>
            <input type="text" name="address_line2" id="address_line2" class="form-control" value="{{ old('address_line2', $employee->address_line2 ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">City</label>
            <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $employee->city ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">District <span class="text-danger">*</span></label>
            <input type="text" name="district" id="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district', $employee->district ?? '') }}" required>
            @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">State <span class="text-danger">*</span></label>
            @php $currentState = old('state', $employee->state ?? ''); @endphp
            <select name="state" id="state" class="form-select @error('state') is-invalid @enderror" data-searchable required>
                <option value="">Select State</option>
                @foreach ($states as $st)
                    <option value="{{ $st }}" {{ $currentState == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
            @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Pincode <span class="text-danger">*</span></label>
            <input type="text" name="pincode" id="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode', $employee->pincode ?? '') }}" required maxlength="6">
            @error('pincode')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 mt-2">
            <div class="form-check">
                <input type="checkbox" name="same_as_current_address" id="same_as_current_address" value="1" class="form-check-input" {{ old('same_as_current_address') ? 'checked' : '' }}>
                <label class="form-check-label" for="same_as_current_address">Permanent address same as current address</label>
            </div>
        </div>
        <div class="col-12"><h6 class="fw-bold border-bottom pb-2 mt-2">Permanent Address</h6></div>
        <div id="permanent-address-fields" class="row g-3">
            <div class="col-12">
                <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                <textarea name="permanent_address_line1" class="form-control @error('permanent_address_line1') is-invalid @enderror" rows="2">{{ old('permanent_address_line1', $employee->permanent_address_line1 ?? '') }}</textarea>
                @error('permanent_address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Address Line 2</label>
                <input type="text" name="permanent_address_line2" class="form-control" value="{{ old('permanent_address_line2', $employee->permanent_address_line2 ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="permanent_city" class="form-control" value="{{ old('permanent_city', $employee->permanent_city ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">District</label>
                <input type="text" name="permanent_district" class="form-control" value="{{ old('permanent_district', $employee->permanent_district ?? '') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">State</label>
                @php $currentPermState = old('permanent_state', $employee->permanent_state ?? ''); @endphp
                <select name="permanent_state" class="form-select">
                    <option value="">Select State</option>
                    @foreach ($states as $st)
                        <option value="{{ $st }}" {{ $currentPermState == $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Pincode</label>
                <input type="text" name="permanent_pincode" class="form-control" value="{{ old('permanent_pincode', $employee->permanent_pincode ?? '') }}" maxlength="6">
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
        <button type="button" class="btn btn-primary wizard-next">Save &amp; Next</button>
        @if($showSaveAsDraft)
            <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
        @else
            <button type="submit" name="next_tab" value="" class="btn btn-outline-primary wizard-save-stay">Save as Draft</button>
        @endif
        <a href="{{ $employee ? route('employees.show', $employee) : route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

{{-- ── Tab 5: Employment Information ───────────────────────────────── --}}
<div class="tab-pane" data-tab-pane="5">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Employee Number</label>
            <input type="text" id="employee_code_display" class="form-control" value="{{ $employee->employee_code ?? '' }}" placeholder="Select Employee Category &amp; Type" disabled>
            <div class="form-text">Auto-generated and read-only.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Employee Category <span class="text-danger">*</span></label>
            <select name="employee_category" id="employee_category" class="form-select @error('employee_category') is-invalid @enderror" required>
                <option value="">Select</option>
                <option value="staff" {{ $currentCategory == 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="company_labour" {{ $currentCategory == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                <option value="contract_labour" {{ $currentCategory == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
            </select>
            @error('employee_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Employee Type <span class="text-danger">*</span></label>
            <select name="employee_type_id" id="employee_type_id" class="form-select @error('employee_type_id') is-invalid @enderror" data-searchable required>
                <option value="">Select</option>
                @foreach ($employeeTypes as $et)
                    <option value="{{ $et->id }}" {{ old('employee_type_id', $employee->employee_type_id ?? null) == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                @endforeach
            </select>
            @error('employee_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @php $currentStatus = old('status', $employee->status ?? 'active'); @endphp
                <option value="active" {{ $currentStatus == 'active' ? 'selected' : '' }}>Active</option>
                <option value="probation" {{ $currentStatus == 'probation' ? 'selected' : '' }}>Probation</option>
                <option value="inactive" {{ $currentStatus == 'inactive' ? 'selected' : '' }}>Inactive</option>
                @if($employee)
                    <option value="terminated" {{ $currentStatus == 'terminated' ? 'selected' : '' }}>Terminated</option>
                @endif
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @if (! $employee)
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input type="checkbox" name="create_user" class="form-check-input" value="1">
                    <label class="form-check-label">Create Login User</label>
                </div>
            </div>
        @endif

        <div class="col-md-3">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" data-searchable required>
                <option value="">Select</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}" {{ old('department_id', $employee->department_id ?? null) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Designation <span class="text-danger">*</span></label>
            <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $employee->designation->name ?? '') }}" required>
            @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Date of Joining <span class="text-danger">*</span></label>
            <input type="date" name="date_of_joining" class="form-control @error('date_of_joining') is-invalid @enderror" value="{{ old('date_of_joining', $employee?->date_of_joining?->format('Y-m-d') ?? '') }}" required>
            @error('date_of_joining')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Shift</label>
            <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror" data-searchable>
                <option value="">Select</option>
                @foreach ($shifts as $s)
                    <option value="{{ $s->id }}" {{ old('shift_id', $employee->shift_id ?? null) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Reporting To</label>
            <select name="reporting_to" class="form-select @error('reporting_to') is-invalid @enderror" data-searchable>
                <option value="">Select</option>
                @foreach ($managers as $m)
                    @if (! $employee || $m->id !== $employee->id)
                        <option value="{{ $m->id }}" {{ old('reporting_to', $employee->reporting_to ?? null) == $m->id ? 'selected' : '' }}>{{ $m->full_name }}</option>
                    @endif
                @endforeach
            </select>
            @error('reporting_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Probation End Date</label>
            <input type="date" name="probation_end_date" class="form-control @error('probation_end_date') is-invalid @enderror" value="{{ old('probation_end_date', $employee?->probation_end_date?->format('Y-m-d') ?? '') }}">
            @error('probation_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Confirmation Date</label>
            <input type="date" name="date_of_confirmation" class="form-control @error('date_of_confirmation') is-invalid @enderror" value="{{ old('date_of_confirmation', $employee?->date_of_confirmation?->format('Y-m-d') ?? '') }}">
            @error('date_of_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @if($canOverrideRules)
        {{-- UI-hidden per current requirements — the fields/backend logic
             stay fully intact (validation, stripUnauthorizedRuleOverrides(),
             BusinessRule::resolveForEmployee()) in case this needs to be
             shown again; only visually hidden via d-none. --}}
        <div class="col-12 d-none">
        <div class="row g-3">
        <div class="col-12 mt-3">
            <h6 class="fw-bold border-bottom pb-2">Rule Engine Overrides <small class="fw-normal text-muted">(optional — leave blank to use automatic branch/type resolution)</small></h6>
        </div>
        <div class="col-md-4">
            <label class="form-label">Weekly Off Rule</label>
            <select name="weekly_off_rule_id" class="form-select">
                <option value="">Automatic</option>
                @foreach ($ruleOptions['weekly_off'] ?? [] as $r)
                    <option value="{{ $r->id }}" {{ old('weekly_off_rule_id', $employee->weekly_off_rule_id ?? null) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Attendance Rule</label>
            <select name="attendance_rule_id" class="form-select">
                <option value="">Automatic</option>
                @foreach ($ruleOptions['attendance'] ?? [] as $r)
                    <option value="{{ $r->id }}" {{ old('attendance_rule_id', $employee->attendance_rule_id ?? null) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Payroll Rule</label>
            <select name="payroll_rule_id" class="form-select">
                <option value="">Automatic</option>
                @foreach ($ruleOptions['payroll'] ?? [] as $r)
                    <option value="{{ $r->id }}" {{ old('payroll_rule_id', $employee->payroll_rule_id ?? null) == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->category }})</option>
                @endforeach
            </select>
            <div class="form-text">Overrides one specific payroll category (LOP/PF/ESI/TDS/Overtime) — pick the rule for the category you need to override.</div>
        </div>
        </div>
        </div>
        @endif
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
        <button type="button" class="btn btn-primary wizard-next">Save &amp; Next</button>
        @if($showSaveAsDraft)
            <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
        @else
            <button type="submit" name="next_tab" value="" class="btn btn-outline-primary wizard-save-stay">Save as Draft</button>
        @endif
        <a href="{{ $employee ? route('employees.show', $employee) : route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

{{-- ── Tab 6: Contract Labour Information (shown only for Contract Labour) ── --}}
<div class="tab-pane" data-tab-pane="6">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Contractor <span class="text-danger">*</span></label>
            <select name="contractor_id" id="contractor_id" class="form-select @error('contractor_id') is-invalid @enderror" data-searchable>
                <option value="">Select</option>
                @foreach ($contractors as $c)
                    <option value="{{ $c->id }}" {{ old('contractor_id', $employee->contractor_id ?? null) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('contractor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Contractor Employee Number</label>
            <input type="text" name="contractor_employee_number" class="form-control" value="{{ old('contractor_employee_number', $employee->contractor_employee_number ?? '') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Work Order Number</label>
            <input type="text" name="work_order_number" class="form-control" value="{{ old('work_order_number', $employee->work_order_number ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Contract Start Date</label>
            <input type="date" name="contract_start_date" class="form-control @error('contract_start_date') is-invalid @enderror" value="{{ old('contract_start_date', $employee?->contract_start_date?->format('Y-m-d') ?? '') }}">
            @error('contract_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Contract End Date</label>
            <input type="date" name="contract_end_date" class="form-control @error('contract_end_date') is-invalid @enderror" value="{{ old('contract_end_date', $employee?->contract_end_date?->format('Y-m-d') ?? '') }}">
            @error('contract_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if ($employee?->isContractExpiringSoon())
                <div class="form-text text-warning">Contract expires soon ({{ $employee->contract_end_date->format('d-M-Y') }}).</div>
            @elseif ($employee?->isContractExpired())
                <div class="form-text text-danger">Contract has expired.</div>
            @endif
        </div>
        <div class="col-md-3">
            <label class="form-label">Labour Category</label>
            <input type="text" name="labour_category" class="form-control" value="{{ old('labour_category', $employee->labour_category ?? '') }}">
        </div>
        @if($canSetContractorRate)
        <div class="col-md-3">
            <label class="form-label">Contractor Rate</label>
            <input type="number" step="0.01" name="contractor_rate" class="form-control" value="{{ old('contractor_rate', $employee->contractor_rate ?? '') }}">
        </div>
        @endif
        <div class="col-12">
            <label class="form-label">Contractor Remarks</label>
            <textarea name="contractor_remarks" class="form-control" rows="2">{{ old('contractor_remarks', $employee->contractor_remarks ?? '') }}</textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
        <button type="button" class="btn btn-primary wizard-next">Save &amp; Next</button>
        @if($showSaveAsDraft)
            <button type="submit" name="save_as_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
        @else
            <button type="submit" name="next_tab" value="" class="btn btn-outline-primary wizard-save-stay">Save as Draft</button>
        @endif
        <a href="{{ $employee ? route('employees.show', $employee) : route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

{{--
    Tab 7 (Statutory Information) has been removed — the identity-document
    fields (Aadhaar/PAN/UAN/PF/ESI numbers, Passport) are no longer
    collected via the Employee Master; the PF/ESI/TDS (and new OT)
    applicability checkboxes moved onto the Designation & Salary tab,
    alongside the Salary Slab that already drives their auto-suggestion.
--}}
