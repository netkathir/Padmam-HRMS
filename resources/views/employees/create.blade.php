@extends('layouts.app')

@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('page-subtitle', 'Create a new employee record')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="fw-bold border-bottom pb-2">Personal Information</h6>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Photo</label>
                        <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png"
                            class="form-control @error('profile_photo') is-invalid @enderror">
                        @error('profile_photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name"
                            class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}"
                            required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name"
                            class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name') }}">
                        @error('middle_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                            value="{{ old('last_name') }}">
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="display_name" class="form-control"
                            value="{{ old('display_name') }}" placeholder="Defaults to full name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee Code</label>
                        <input type="text" name="employee_code"
                            class="form-control @error('employee_code') is-invalid @enderror"
                            value="{{ old('employee_code') }}" placeholder="Auto-generated if a numbering rule applies">
                        <div class="form-text">Leave blank to auto-generate from the Rule Engine's Employee Number Rule, if one is configured.</div>
                        @error('employee_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Biometric ID <span class="text-danger">*</span></label>
                        <input type="text" name="biometric_id"
                            class="form-control @error('biometric_id') is-invalid @enderror" value="{{ old('biometric_id') }}" required>
                        @error('biometric_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee Category <span class="text-danger">*</span></label>
                        <select name="employee_category" id="employee_category" class="form-select @error('employee_category') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="staff" {{ old('employee_category') == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="company_labour" {{ old('employee_category') == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                            <option value="contract_labour" {{ old('employee_category') == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                        </select>
                        @error('employee_category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth"
                            class="form-control @error('date_of_birth') is-invalid @enderror"
                            value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select</option>
                            <option value="single" {{ old('marital_status') == 'single' ? 'selected' : '' }}>Single
                            </option>
                            <option value="married" {{ old('marital_status') == 'married' ? 'selected' : '' }}>Married
                            </option>
                            <option value="divorced" {{ old('marital_status') == 'divorced' ? 'selected' : '' }}>Divorced
                            </option>
                            <option value="widowed" {{ old('marital_status') == 'widowed' ? 'selected' : '' }}>Widowed
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Blood Group</label>
                        <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Father / Spouse Name</label>
                        <input type="text" name="father_spouse_name" class="form-control @error('father_spouse_name') is-invalid @enderror" value="{{ old('father_spouse_name') }}">
                        @error('father_spouse_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nationality</label>
                        <input type="text" name="nationality" class="form-control" value="{{ old('nationality', 'Indian') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Religion</label>
                        <input type="text" name="religion" class="form-control" value="{{ old('religion') }}">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Contact Information</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Official Email <span class="text-danger">*</span></label>
                        <input type="email" name="official_email"
                            class="form-control @error('official_email') is-invalid @enderror"
                            value="{{ old('official_email') }}" required>
                        @error('official_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Personal Email</label>
                        <input type="email" name="personal_email" class="form-control"
                            value="{{ old('personal_email') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Alternate Phone</label>
                        <input type="text" name="alternate_phone" class="form-control @error('alternate_phone') is-invalid @enderror" value="{{ old('alternate_phone') }}">
                        @error('alternate_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" class="form-control @error('emergency_contact_phone') is-invalid @enderror" value="{{ old('emergency_contact_phone') }}">
                        @error('emergency_contact_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Emergency Contact Relationship</label>
                        <input type="text" name="emergency_contact_relationship" class="form-control" value="{{ old('emergency_contact_relationship') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Current Address <span class="text-danger">*</span></label>
                        <textarea name="address_line1" class="form-control @error('address_line1') is-invalid @enderror" rows="2" required>{{ old('address_line1') }}</textarea>
                        @error('address_line1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">District <span class="text-danger">*</span></label>
                        <input type="text" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district') }}" required>
                        @error('district')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">State <span class="text-danger">*</span></label>
                        <select name="state" class="form-select @error('state') is-invalid @enderror" required>
                            <option value="">Select State</option>
                            @foreach ($states as $st)
                                <option value="{{ $st }}" {{ old('state') == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                        @error('state')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Pincode <span class="text-danger">*</span></label>
                        <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode') }}" required maxlength="6">
                        @error('pincode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="same_as_current_address" id="same_as_current_address" value="1" class="form-check-input" {{ old('same_as_current_address') ? 'checked' : '' }}>
                            <label class="form-check-label" for="same_as_current_address">Permanent address same as current address</label>
                        </div>
                    </div>
                    <div id="permanent-address-fields" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Permanent Address <span class="text-danger">*</span></label>
                            <textarea name="permanent_address_line1" class="form-control @error('permanent_address_line1') is-invalid @enderror" rows="2">{{ old('permanent_address_line1') }}</textarea>
                            @error('permanent_address_line1')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="permanent_city" class="form-control" value="{{ old('permanent_city') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">District</label>
                            <input type="text" name="permanent_district" class="form-control" value="{{ old('permanent_district') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">State</label>
                            <select name="permanent_state" class="form-select">
                                <option value="">Select State</option>
                                @foreach ($states as $st)
                                    <option value="{{ $st }}" {{ old('permanent_state') == $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="permanent_pincode" class="form-control" value="{{ old('permanent_pincode') }}" maxlength="6">
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Job Information</h6>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required {{ $lockedBranchId ? 'disabled' : '' }}>
                            <option value="">Select</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) old('branch_id', $lockedBranchId) === (string) $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}</option>
                            @endforeach
                        </select>
                        @if ($lockedBranchId)
                            <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                        @endif
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror"
                            required>
                            <option value="">Select</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}"
                                    {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror"
                            required>
                            <option value="">Select</option>
                            @foreach ($designations as $des)
                                <option value="{{ $des->id }}"
                                    {{ old('designation_id') == $des->id ? 'selected' : '' }}>{{ $des->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee Type <span class="text-danger">*</span></label>
                        <select name="employee_type_id"
                            class="form-select @error('employee_type_id') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach ($employeeTypes as $et)
                                <option value="{{ $et->id }}"
                                    {{ old('employee_type_id') == $et->id ? 'selected' : '' }}>{{ $et->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Joining <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_joining"
                            class="form-control @error('date_of_joining') is-invalid @enderror"
                            value="{{ old('date_of_joining') }}" required>
                        @error('date_of_joining')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="probation" {{ old('status') == 'probation' ? 'selected' : '' }}>Probation
                            </option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Shift</label>
                        <select name="shift_id" class="form-select">
                            <option value="">Select</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reporting To</label>
                        <select name="reporting_to" class="form-select">
                            <option value="">Select</option>
                            @foreach ($managers as $m)
                                <option value="{{ $m->id }}"
                                    {{ old('reporting_to') == $m->id ? 'selected' : '' }}>{{ $m->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Probation End Date</label>
                        <input type="date" name="probation_end_date" class="form-control @error('probation_end_date') is-invalid @enderror" value="{{ old('probation_end_date') }}">
                        @error('probation_end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Confirmation Date</label>
                        <input type="date" name="date_of_confirmation" class="form-control @error('date_of_confirmation') is-invalid @enderror" value="{{ old('date_of_confirmation') }}">
                        @error('date_of_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($canOverrideRules)
                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Rule Engine Overrides <small class="fw-normal text-muted">(optional — leave blank to use automatic branch/type resolution)</small></h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Weekly Off Rule</label>
                        <select name="weekly_off_rule_id" class="form-select">
                            <option value="">Automatic</option>
                            @foreach ($ruleOptions['weekly_off'] ?? [] as $r)
                                <option value="{{ $r->id }}" {{ old('weekly_off_rule_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Attendance Rule</label>
                        <select name="attendance_rule_id" class="form-select">
                            <option value="">Automatic</option>
                            @foreach ($ruleOptions['attendance'] ?? [] as $r)
                                <option value="{{ $r->id }}" {{ old('attendance_rule_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payroll Rule</label>
                        <select name="payroll_rule_id" class="form-select">
                            <option value="">Automatic</option>
                            @foreach ($ruleOptions['payroll'] ?? [] as $r)
                                <option value="{{ $r->id }}" {{ old('payroll_rule_id') == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->category }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Overrides one specific payroll category (LOP/PF/ESI/TDS/Overtime) — pick the rule for the category you need to override.</div>
                    </div>
                    @endif

                    <div id="contract-labour-section" class="row g-3">
                        <div class="col-12 mt-3">
                            <h6 class="fw-bold border-bottom pb-2">Contract Labour Information</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contractor</label>
                            <select name="contractor_id" class="form-select @error('contractor_id') is-invalid @enderror">
                                <option value="">Select</option>
                                @foreach ($contractors as $c)
                                    <option value="{{ $c->id }}" {{ old('contractor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('contractor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contractor Employee Number</label>
                            <input type="text" name="contractor_employee_number" class="form-control" value="{{ old('contractor_employee_number') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Work Order Number</label>
                            <input type="text" name="work_order_number" class="form-control" value="{{ old('work_order_number') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Contract Start Date</label>
                            <input type="date" name="contract_start_date" class="form-control @error('contract_start_date') is-invalid @enderror" value="{{ old('contract_start_date') }}">
                            @error('contract_start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Contract End Date</label>
                            <input type="date" name="contract_end_date" class="form-control @error('contract_end_date') is-invalid @enderror" value="{{ old('contract_end_date') }}">
                            @error('contract_end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Labour Category</label>
                            <input type="text" name="labour_category" class="form-control" value="{{ old('labour_category') }}">
                        </div>
                        @if($canSetContractorRate)
                        <div class="col-md-3">
                            <label class="form-label">Contractor Rate</label>
                            <input type="number" step="0.01" name="contractor_rate" class="form-control" value="{{ old('contractor_rate') }}">
                        </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label">Contractor Remarks</label>
                            <textarea name="contractor_remarks" class="form-control" rows="2">{{ old('contractor_remarks') }}</textarea>
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Government IDs</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Aadhaar Number</label>
                        <input type="text" name="aadhaar_number" class="form-control @error('aadhaar_number') is-invalid @enderror"
                            value="{{ old('aadhaar_number') }}" maxlength="12">
                        @error('aadhaar_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="pan_number" class="form-control @error('pan_number') is-invalid @enderror" value="{{ old('pan_number') }}" maxlength="10" style="text-transform:uppercase">
                        @error('pan_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">UAN Number</label>
                        <input type="text" name="uan_number" class="form-control @error('uan_number') is-invalid @enderror" value="{{ old('uan_number') }}" maxlength="12">
                        @error('uan_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PF Number</label>
                        <input type="text" name="pf_number" class="form-control @error('pf_number') is-invalid @enderror" value="{{ old('pf_number') }}">
                        @error('pf_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ESI Number</label>
                        <input type="text" name="esi_number" class="form-control @error('esi_number') is-invalid @enderror" value="{{ old('esi_number') }}" maxlength="10">
                        @error('esi_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Passport Number</label>
                        <input type="text" name="passport_number" class="form-control" value="{{ old('passport_number') }}">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Payroll Settings</h6>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_pf_applicable" class="form-check-input" value="1"
                                {{ old('is_pf_applicable', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">PF Applicable</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_esi_applicable" class="form-check-input" value="1"
                                {{ old('is_esi_applicable', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">ESI Applicable</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_tds_applicable" class="form-check-input" value="1"
                                {{ old('is_tds_applicable') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">TDS Applicable</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="create_user" class="form-check-input" value="1">
                            <label class="form-check-label">Create Login User</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const categorySelect = document.getElementById('employee_category');
        const contractSection = document.getElementById('contract-labour-section');
        const sameAsCurrent = document.getElementById('same_as_current_address');
        const permanentFields = document.getElementById('permanent-address-fields');

        function toggleContractSection() {
            const isContractLabour = categorySelect.value === 'contract_labour';
            contractSection.style.display = isContractLabour ? '' : 'none';
        }

        function togglePermanentFields() {
            permanentFields.style.display = sameAsCurrent.checked ? 'none' : '';
        }

        if (categorySelect && contractSection) {
            categorySelect.addEventListener('change', toggleContractSection);
            toggleContractSection();
        }
        if (sameAsCurrent && permanentFields) {
            sameAsCurrent.addEventListener('change', togglePermanentFields);
            togglePermanentFields();
        }
    })();
</script>
@endpush
