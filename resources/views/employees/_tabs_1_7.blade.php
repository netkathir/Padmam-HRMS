{{--
    Create Employee wizard — Steps 1-3 only (Personal Information, Contact
    Information, Address Information). Employment Information, Bank
    Information, Designation & Salary, and Employee Documents were moved to
    the separate Employee Slab / Employee Document modules (People Module
    Update) — this file no longer renders them. Included by both
    create.blade.php and edit.blade.php inside their Steps-1-3 form.

    Expects: $employee (Employee|null — null on Create), $currentBranch, $states.
--}}
{{-- FSD Rule 1 — Branch is never shown; it always follows the currently active branch. --}}
<input type="hidden" name="branch_id" value="{{ $currentBranch->id ?? '' }}">

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
        <button type="submit" name="finish" value="1" class="btn btn-success">Save Employee</button>
        <a href="{{ $employee ? route('employees.show', $employee) : route('employees.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

{{--
    Employment Information, Contract Labour Information, Bank Information,
    Designation & Salary, and Employee Documents were moved out of this
    wizard entirely — they now live in the separate Employee Slab / Employee
    Document modules (see resources/views/employee-slab and
    resources/views/employee-document), each its own single-step form.
--}}
